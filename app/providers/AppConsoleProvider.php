<?php declare(strict_types=1);

namespace app\providers;

use src\container\ContainerInterface;
use src\container\provider\ProviderInterface;

/**
 * @package app\providers
 */
final class AppConsoleProvider implements ProviderInterface
{
    /**
     * @param ContainerInterface $container
     */
    public function register(ContainerInterface $container): void
    {
        // place for app-specific CLI bindings later
    }
}
