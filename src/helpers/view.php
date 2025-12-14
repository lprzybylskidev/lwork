<?php declare(strict_types=1);

/**
 * @package src\helpers
 */

use Twig\Environment as TwigEnvironment;

if (!function_exists('view')) {
    /**
     * @throws RuntimeException
     */
    function view(): TwigEnvironment
    {
        $container = env_container();

        if ($container === null) {
            throw new RuntimeException(
                'Container not available for view() helper.',
            );
        }

        if (!$container->has(TwigEnvironment::class)) {
            throw new RuntimeException('Twig environment is not registered.');
        }

        return $container->get(TwigEnvironment::class);
    }
}
