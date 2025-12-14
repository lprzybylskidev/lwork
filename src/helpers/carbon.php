<?php declare(strict_types=1);

/**
 * @package src\helpers
 */

use Carbon\CarbonImmutable;
use src\datetime\CarbonFactory;

if (!function_exists('carbon')) {
    /**
     * @param string $time
     * @throws \RuntimeException
     * @return CarbonImmutable
     */
    function carbon(string $time = 'now'): CarbonImmutable
    {
        $container = env_container();

        if ($container === null) {
            throw new \RuntimeException(
                'Container not available for carbon() helper.',
            );
        }

        $factory = $container->get(CarbonFactory::class);

        return $factory->create($time);
    }
}

if (!function_exists('now')) {
    /**
     * @return CarbonImmutable
     */
    function now(): CarbonImmutable
    {
        return carbon('now');
    }
}
