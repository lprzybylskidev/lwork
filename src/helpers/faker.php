<?php declare(strict_types=1);

/**
 * @package src\helpers
 */

use Faker\Generator;
use src\container\ContainerInterface;

if (!function_exists('faker')) {
    /**
     * @throws \RuntimeException
     */
    function faker(): Generator
    {
        $container = env_container();

        if ($container === null) {
            throw new \RuntimeException(
                'Container not available for faker() helper.',
            );
        }

        return $container->get(Generator::class);
    }
}
