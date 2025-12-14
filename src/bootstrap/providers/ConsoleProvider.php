<?php

declare(strict_types=1);

namespace src\bootstrap\providers;

use src\container\ContainerInterface;
use src\container\provider\ProviderInterface;

/**
 * @package src\bootstrap\providers
 */
final class ConsoleProvider implements ProviderInterface
{
    /**
     * @param ContainerInterface $container
     */
    public function register(ContainerInterface $container): void {}
}
