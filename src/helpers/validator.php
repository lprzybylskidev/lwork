<?php declare(strict_types=1);

/**
 * @package src\helpers
 */

use src\validation\Validator;

if (!function_exists('validator')) {
    /**
     * @throws RuntimeException
     */
    function validator(): Validator
    {
        $container = env_container();

        if ($container === null) {
            throw new RuntimeException(
                'Container not available for validator() helper.',
            );
        }

        return $container->get(Validator::class);
    }
}
