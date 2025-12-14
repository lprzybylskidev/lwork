<?php

declare(strict_types=1);

namespace src\bootstrap\providers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use src\container\ContainerInterface;
use src\container\provider\ProviderInterface;
use src\http\emitter\ResponseEmitter;
use src\http\middleware\Dispatcher;
use src\http\exceptions\HttpException;
use src\http\routing\Router;
use src\http\routing\RouteGenerator;
use src\http\routing\RouterMiddleware;
use src\security\CsrfMiddleware;
use src\security\SecurityHeadersMiddleware;
use src\security\ThrottleMiddleware;

/**
 * @package src\bootstrap\providers
 */
final class HttpProvider implements ProviderInterface
{
    /**
     * @param ContainerInterface $container
     */
    public function register(ContainerInterface $container): void
    {
        $container->singleton(Router::class);
        $container->singleton(RouteGenerator::class, function (
            ContainerInterface $c,
        ): RouteGenerator {
            return new RouteGenerator($c->get(Router::class));
        });
        $container->singleton(ResponseEmitter::class);
        $container->singleton(RouterMiddleware::class);

        $container->bind(Dispatcher::class, function (
            ContainerInterface $c,
        ): Dispatcher {
            $fallback = new class implements RequestHandlerInterface {
                public function handle(
                    ServerRequestInterface $request,
                ): ResponseInterface {
                    throw new HttpException(404, 'Not Found');
                }
            };

            return new Dispatcher(
                [
                    $c->get(SecurityHeadersMiddleware::class),
                    $c->get(ThrottleMiddleware::class),
                    $c->get(CsrfMiddleware::class),
                    $c->get(RouterMiddleware::class),
                ],
                $fallback,
            );
        });
    }
}
