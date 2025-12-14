<?php

declare(strict_types=1);

namespace src\container\provider;

use src\container\ContainerInterface;

/**
 * @package src\container\provider
 */
interface ProviderInterface
{
    /**
     * @param ContainerInterface $container
     * @return void
     */
    public function register(ContainerInterface $container): void;
}
