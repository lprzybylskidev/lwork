<?php declare(strict_types=1);

namespace src\twig\provider;

use Twig\TwigFilter;

/**
 * @package src\twig\provider
 */
interface TwigFilterProviderInterface
{
    /**
     * @return array<int, TwigFilter>
     */
    public function filters(): array;
}
