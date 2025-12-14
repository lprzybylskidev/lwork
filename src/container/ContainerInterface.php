<?php

declare(strict_types=1);

namespace src\container;

use Closure;

/**
 * @package src\container
 */
interface ContainerInterface
{
    /**
     * @param string $id
     * @return object
     */
    public function get(string $id): object;

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool;

    /**
     * @param string $abstract
     * @param string $concrete
     * @return void
     */
    public function alias(string $abstract, string $concrete): void;

    /**
     * @param string $id
     * @param Closure $factory
     * @return void
     */
    public function bind(string $id, Closure $factory): void;

    /**
     * @param string $id
     * @param Closure|string|null $factoryOrClass
     * @return void
     */
    public function singleton(
        string $id,
        Closure|string|null $factoryOrClass = null,
    ): void;

    /**
     * @param string $id
     * @param object $value
     * @return void
     */
    public function instance(string $id, object $value): void;

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setParam(string $key, mixed $value): void;

    /**
     * @param string $key
     * @return bool
     */
    public function hasParam(string $key): bool;

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function param(string $key, mixed $default = null): mixed;

    /**
     * @param callable $callable
     * @param array<string, mixed> $overrides
     * @return mixed
     */
    public function call(callable $callable, array $overrides = []): mixed;
}
