<?php declare(strict_types=1);

namespace src\twig\provider;

/**
 * @package src\twig\provider
 */
interface TwigGlobalProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function globals(): array;
}
