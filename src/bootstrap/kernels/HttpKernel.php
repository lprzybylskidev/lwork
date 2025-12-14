<?php declare(strict_types=1);

namespace src\bootstrap\kernels;

use function config;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use src\bootstrap\errors\ErrorCode;
use src\container\ContainerInterface;
use src\http\emitter\ResponseEmitter;
use src\http\middleware\Dispatcher;
use src\http\routing\RouterMiddleware;
use src\http\response\Responses;
use src\http\routing\RouteDefinition;
use src\http\routing\Router;
use src\bootstrap\errors\FrameworkPrettyPageHandler;
use src\environment\Env;
use src\http\exceptions\HttpException;
use src\log\ErrorFileLogger;
use src\session\SessionManager;
use Whoops\Run;

/**
 * @package src\bootstrap\kernels
 */
final class HttpKernel implements KernelInterface
{
    private ?ServerRequestInterface $currentRequest = null;

    /**
     * @param ContainerInterface $container
     * @param ResponseEmitter $emitter
     */
    public function __construct(
        private ContainerInterface $container,
        private ResponseEmitter $emitter,
    ) {}

    /**
     * @return int
     */
    public function handle(): int
    {
        $request = $this->createServerRequest();
        $this->currentRequest = $request;

        $dispatcher = $this->container->get(Dispatcher::class);
        $response = $dispatcher->handle($request);

        $this->emitter->emit($response);

        return 0;
    }

    /**
     * @param \Throwable $e
     * @return int
     */
    public function handleException(\Throwable $e): int
    {
        $request = $this->currentRequest;
        $isApi = $this->isApiRequest($request);
        $status = $this->resolveStatusCode($e);
        $errorCode = ErrorCode::generate();

        $this->container->get(ErrorFileLogger::class)->log($e, $errorCode, [
            'mode' => 'http',
            'status' => $status,
            'is_api' => $isApi ? 1 : 0,
            'method' => $request?->getMethod(),
            'uri' => $request?->getUri()->__toString(),
            'env' => $this->appEnv(),
        ]);

        $response = $this->buildExceptionResponse(
            $e,
            $status,
            $isApi,
            $errorCode,
            $request,
        );

        $this->emitter->emit($response);

        return 1;
    }

    /**
     * @param \Throwable $e
     * @param int $status
     * @param bool $isApi
     * @param string $errorCode
     * @param ServerRequestInterface|null $request
     * @return ResponseInterface
     */
    private function buildExceptionResponse(
        \Throwable $e,
        int $status,
        bool $isApi,
        string $errorCode,
        ?ServerRequestInterface $request,
    ): ResponseInterface {
        if ($this->isLocal()) {
            if ($isApi) {
                return Responses::json(
                    $this->buildLocalApiPayload($e, $status, $errorCode),
                    $status,
                );
            }

            $body = $this->renderWhoopsPage($e, $errorCode, $status, $request);
            return Responses::html($body, $status);
        }

        if ($isApi) {
            return Responses::json(
                [
                    'code' => $status,
                    'error_code' => $errorCode,
                ],
                $status,
            );
        }

        return $this->renderProductionError($status, $errorCode);
    }

    /**
     * @param int $status
     * @param string $errorCode
     * @return ResponseInterface
     */
    private function renderProductionError(
        int $status,
        string $errorCode,
    ): ResponseInterface {
        try {
            return $this->dispatchErrorRoute($status, $errorCode);
        } catch (\Throwable) {
            return Responses::text(
                sprintf("Something went wrong (%s).\n", $errorCode),
                $status,
            );
        }
    }

    /**
     * @param \Throwable $e
     * @param int $status
     * @param string $errorCode
     * @return array<string, mixed>
     */
    private function buildLocalApiPayload(
        \Throwable $e,
        int $status,
        string $errorCode,
    ): array {
        return [
            'code' => $status,
            'error_code' => $errorCode,
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_map(
                fn(array $frame) => [
                    'file' => $frame['file'] ?? null,
                    'line' => $frame['line'] ?? null,
                    'function' => $frame['function'] ?? null,
                    'class' => $frame['class'] ?? null,
                ],
                $e->getTrace(),
            ),
        ];
    }

    /**
     * @param \Throwable $e
     * @param string $errorCode
     * @param int $status
     * @param ServerRequestInterface|null $request
     * @return string
     */
    private function renderWhoopsPage(
        \Throwable $e,
        string $errorCode,
        int $status,
        ?ServerRequestInterface $request,
    ): string {
        $whoops = new Run();

        $handler = new FrameworkPrettyPageHandler(
            $this->container->get(Env::class),
            $this->container->get(SessionManager::class),
        );
        $handler->setRequest($request);
        $handler->setContext(
            $status,
            $errorCode,
            $this->isApiRequest($request),
        );

        $whoops->pushHandler($handler);

        ob_start();
        $whoops->handleException($e);
        return (string) ob_get_clean();
    }

    /**
     * @param int $status
     * @param string $errorCode
     * @return ResponseInterface
     */
    private function dispatchErrorRoute(
        int $status,
        string $errorCode,
    ): ResponseInterface {
        $router = $this->container->get(Router::class);
        $route = $router->findErrorRoute($status);

        if ($route === null) {
            throw new \RuntimeException('No error route available.');
        }

        return $this->callRouteHandler($route, $status, $errorCode);
    }

    /**
     * @param RouteDefinition $route
     * @param int $status
     * @param string $errorCode
     * @return ResponseInterface
     */
    private function callRouteHandler(
        RouteDefinition $route,
        int $status,
        string $errorCode,
    ): ResponseInterface {
        $request = $this->currentRequest ?? $this->createServerRequest();
        $uri = $request
            ->getUri()
            ->withPath("/error/{$status}")
            ->withQuery('');
        $errorRequest = $request
            ->withUri($uri)
            ->withMethod('GET')
            ->withAttribute('code', $status)
            ->withAttribute('error_code', $errorCode);

        $handler = $this->resolveHandler($route->handler());
        $overrides = [
            'request' => $errorRequest,
            ServerRequestInterface::class => $errorRequest,
            'code' => $status,
            'error_code' => $errorCode,
        ];

        return $this->container->call($handler, $overrides);
    }

    /**
     * @param callable|string $handler
     * @return callable
     */
    private function resolveHandler(callable|string $handler): callable
    {
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $instance = $this->container->get($class);
            return [$instance, $method];
        }

        if (is_string($handler) && class_exists($handler)) {
            $instance = $this->container->get($handler);

            if (is_callable($instance)) {
                return $instance;
            }
        }

        return $handler;
    }

    /**
     * @return ServerRequestInterface
     */
    private function createServerRequest(): ServerRequestInterface
    {
        $factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $factory,
            $factory,
            $factory,
            $factory,
        );

        return $creator->fromGlobals();
    }

    /**
     * @param ServerRequestInterface|null $request
     * @return bool
     */
    private function isApiRequest(?ServerRequestInterface $request): bool
    {
        if ($request === null) {
            return false;
        }

        $accept = strtolower($request->getHeaderLine('Accept'));
        $contentType = strtolower($request->getHeaderLine('Content-Type'));
        $uri = strtolower($request->getUri()->getPath());

        if (str_contains($accept, 'application/json')) {
            return true;
        }

        if (str_contains($contentType, 'application/json')) {
            return true;
        }

        return str_starts_with($uri, '/api');
    }

    /**
     * @param \Throwable $e
     * @return int
     */
    private function resolveStatusCode(\Throwable $e): int
    {
        if ($e instanceof HttpException) {
            $status = $e->statusCode();
            return $status >= 400 && $status <= 599 ? $status : 500;
        }

        return 500;
    }

    /**
     * @return string
     */
    private function appEnv(): string
    {
        return config('app.env', 'production');
    }

    /**
     * @return bool
     */
    private function isLocal(): bool
    {
        return $this->appEnv() === 'local';
    }
}
