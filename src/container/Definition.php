<?php

declare(strict_types=1);

namespace Src\Container;

use Closure;

/**
 * @package Src\Container
 */
final class Definition
{
    /**
     * @param bool $shared
     * @param Closure|string $resolver
     */
    public function __construct(
        public readonly bool $shared,
        public readonly Closure|string $resolver,
    ) {}
}
