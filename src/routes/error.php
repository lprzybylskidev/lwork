<?php declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface;
use src\container\ContainerInterface;
use src\http\response\Responses;
use src\http\routing\Router;
use src\twig\FrameworkTwigEnvironment;

return static function (Router $router, ContainerInterface $container): void {
    $router->get(
        '/error/{code}',
        function (ServerRequestInterface $request) use ($container) {
            $twig = $container->get(FrameworkTwigEnvironment::class);

            $status = (int) ($request->getAttribute('code') ?? 500);
            $errorCode = $request->getAttribute('error_code', 'N/A');

            $content = $twig->render('error.twig', [
                'status' => $status,
                'error_code' => $errorCode,
            ]);

            return Responses::html($content, $status);
        },
        ['name' => 'error.default'],
    );
};
