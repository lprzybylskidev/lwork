<?php

declare(strict_types=1);

namespace src\container\provider;

use src\container\ContainerInterface;

/**
 * @package src\container\provider
 */
final readonly class ProviderRunner
{
    /**
     * @param array<int, class-string> $providers
     */
    public function __construct(private array $providers) {}

    /**
     * @param ContainerInterface $container
     * @return void
     */
    public function run(ContainerInterface $container): void
    {
        foreach ($this->providers as $providerClass) {
            $provider = new $providerClass();
            $provider->register($container);
        }
    }
}
