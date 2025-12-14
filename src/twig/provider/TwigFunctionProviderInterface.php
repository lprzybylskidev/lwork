<?php declare(strict_types=1);

namespace src\twig\provider;

use Twig\TwigFunction;

/**
 * @package src\twig\provider
 */
interface TwigFunctionProviderInterface
{
    /**
     * @return array<int, TwigFunction>
     */
    public function functions(): array;
}
