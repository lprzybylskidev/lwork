<?php declare(strict_types=1);

use src\http\response\Responses;
use src\http\routing\Router;

return static function (Router $router): void {
    $router->get(
        '/api',
        static function () {
            return Responses::json([
                'message' => 'api works',
                'status' => 'ok',
            ]);
        },
        ['name' => 'api'],
    );
};
