<?php declare(strict_types=1);

use src\http\response\Responses;
use src\http\routing\Router;

return static function (Router $router): void {
    $router->get(
        '/',
        static function () {
            return Responses::text('Hello world', 200);
        },
        ['name' => 'home'],
    );
};
