<?php declare(strict_types=1);

/**
 * @package src\helpers
 */

use src\config\ConfigManager;
use src\container\ContainerInterface;

if (!function_exists('config')) {
    /**
     * @param mixed $default
     */
    function config(string $key, mixed $default = null): mixed
    {
        $container = env_container();

        if ($container === null) {
            throw new RuntimeException(
                'Container not available for config() helper.',
            );
        }

        if (!$container->has(ConfigManager::class)) {
            return $default;
        }

        $manager = $container->get(ConfigManager::class);

        return $manager->get($key, $default);
    }
}
