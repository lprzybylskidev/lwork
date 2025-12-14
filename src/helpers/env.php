<?php declare(strict_types=1);

/**
 * @package src\helpers
 */

use src\container\ContainerInterface;
use src\environment\Env;

if (!function_exists('env')) {
    /**
     * @throws RuntimeException
     */
    function env(): Env
    {
        $container = env_container();

        if ($container === null) {
            throw new RuntimeException(
                'Container not available for env() helper.',
            );
        }

        return $container->get(Env::class);
    }
}

if (!function_exists('env_set_container')) {
    /**
     * @internal
     * @param ContainerInterface $container
     * @return void
     */
    function env_set_container(ContainerInterface $container): void
    {
        env_container($container);
    }
}

if (!function_exists('env_container')) {
    /**
     * @param ContainerInterface|null $container
     * @return ContainerInterface|null
     */
    function env_container(
        ?ContainerInterface $container = null,
    ): ?ContainerInterface {
        static $store = null;

        if ($container !== null) {
            $store = $container;
        }

        return $store;
    }
}
