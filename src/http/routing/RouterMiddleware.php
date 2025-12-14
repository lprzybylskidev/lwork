<?php declare(strict_types=1);

namespace src\http\routing;

use FastRoute\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use src\container\ContainerInterface;
use src\http\exceptions\HttpException;
use src\http\middleware\Dispatcher as MiddlewareDispatcher;
use src\http\routing\RouteDefinition;
use function http_current_request;

/**
 * @package src\http\routing
 */
final class RouterMiddleware implements MiddlewareInterface
{
    /**
     * @param Router $router
     * @param ContainerInterface $container
     */
    public function __construct(
        private Router $router,
        private ContainerInterface $container,
    ) {}

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $result = $this->router->dispatch($request);

        switch ($result[0]) {
            case Dispatcher::NOT_FOUND:
                throw new HttpException(404, 'Not Found');
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new HttpException(405, 'Method Not Allowed');
            case Dispatcher::FOUND:
                /** @var RouteDefinition $route */
                $route = $result[1];
                $params = $result[2];
                break;
            default:
                throw new HttpException(500, 'Routing failure');
        }

        $requestWithAttributes = $this->populateRouteAttributes(
            $request,
            $params,
        );

        http_current_request($requestWithAttributes);

        $resolved = $this->resolveHandler($route->handler());

        return $this->executeRoutePipeline(
            $requestWithAttributes,
            $resolved,
            $route->middleware(),
            $params,
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param array<string, mixed> $params
     */
    private function populateRouteAttributes(
        ServerRequestInterface $request,
        array $params,
    ): ServerRequestInterface {
        $request = $request->withAttribute('route_params', $params);

        foreach ($params as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        return $request;
    }

    /**
     * @param ServerRequestInterface $request
     * @param callable $handler
     * @param array<int, string> $middleware
     * @param array<string, mixed> $params
     * @return ResponseInterface
     */
    private function executeRoutePipeline(
        ServerRequestInterface $request,
        callable $handler,
        array $middleware,
        array $params,
    ): ResponseInterface {
        if ($middleware === []) {
            return $this->handleRoute($request, $handler, $params);
        }

        $instances = $this->resolveMiddlewareInstances($middleware);

        $fallback = new class ($this, $handler, $params) implements
            RequestHandlerInterface
        {
            public function __construct(
                private RouterMiddleware $parent,
                private $handler,
                private array $params,
            ) {}

            public function handle(
                ServerRequestInterface $request,
            ): ResponseInterface {
                return $this->parent->handleRoute(
                    $request,
                    $this->handler,
                    $this->params,
                );
            }
        };

        $dispatcher = new MiddlewareDispatcher($instances, $fallback);

        return $dispatcher->handle($request);
    }

    /**
     * @param array<int, string> $middleware
     *
     * @return array<int, MiddlewareInterface>
     */
    private function resolveMiddlewareInstances(array $middleware): array
    {
        $instances = [];

        foreach ($middleware as $item) {
            if (is_string($item)) {
                $instance = $this->container->get($item);
            } elseif ($item instanceof MiddlewareInterface) {
                $instance = $item;
            } else {
                throw new HttpException(
                    500,
                    'Invalid middleware registered for route.',
                );
            }

            if (!$instance instanceof MiddlewareInterface) {
                throw new HttpException(
                    500,
                    sprintf(
                        'Middleware %s must implement MiddlewareInterface.',
                        is_object($instance)
                            ? $instance::class
                            : (string) $item,
                    ),
                );
            }

            $instances[] = $instance;
        }

        return $instances;
    }

    /**
     * @param ServerRequestInterface $request
     * @param callable $handler
     * @param array<string, mixed> $params
     * @return ResponseInterface
     */
    public function handleRoute(
        ServerRequestInterface $request,
        callable $handler,
        array $params,
    ): ResponseInterface {
        $overrides = $this->buildOverrides($request, $params);
        return $this->container->call($handler, $overrides);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function buildOverrides(
        ServerRequestInterface $request,
        array $params,
    ): array {
        $overrides = [
            'request' => $request,
            ServerRequestInterface::class => $request,
        ];

        foreach ($params as $name => $value) {
            $overrides[$name] = $value;
        }

        return $overrides;
    }

    /**
     * @param callable|string $handler
     *
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
}
