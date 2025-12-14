<?php

declare(strict_types=1);

namespace src\container;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use src\container\exceptions\AutowireException;
use src\container\exceptions\CircularDependencyException;
use src\container\exceptions\NotFoundException;

/**
 * @package src\container
 */
final class Container implements ContainerInterface
{
    private array $aliases = [];

    private array $definitions = [];

    private array $instances = [];

    private array $resolving = [];

    private array $params = [];

    /**
     * @param string $id
     * @return object
     */
    public function get(string $id): object
    {
        $id = $this->normalize($id);

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->resolving[$id])) {
            $path =
                implode(' -> ', array_keys($this->resolving)) . ' -> ' . $id;
            throw new CircularDependencyException(
                "Circular dependency detected: {$path}",
            );
        }

        $this->resolving[$id] = true;

        try {
            $obj = $this->resolve($id);

            unset($this->resolving[$id]);
            return $obj;
        } catch (\Throwable $e) {
            unset($this->resolving[$id]);
            throw $e;
        }
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        $id = $this->normalize($id);

        if (isset($this->instances[$id])) {
            return true;
        }
        if (isset($this->definitions[$id])) {
            return true;
        }

        if (class_exists($id)) {
            $rc = new ReflectionClass($id);
            return $rc->isInstantiable();
        }

        return false;
    }

    /**
     * @param string $abstract
     * @param string $concrete
     * @return void
     */
    public function alias(string $abstract, string $concrete): void
    {
        $this->aliases[$abstract] = $concrete;
    }

    /**
     * @param string $id
     * @param Closure $factory
     * @return void
     */
    public function bind(string $id, Closure $factory): void
    {
        $id = $this->normalize($id);
        unset($this->instances[$id]);
        $this->definitions[$id] = new Definition(
            shared: false,
            resolver: $factory,
        );
    }

    /**
     * @param string $id
     * @param Closure|string|null $factoryOrClass
     * @return void
     */
    public function singleton(
        string $id,
        Closure|string|null $factoryOrClass = null,
    ): void {
        $id = $this->normalize($id);
        unset($this->instances[$id]);

        $resolver = $factoryOrClass ?? $id;

        if (is_string($resolver)) {
            $this->definitions[$id] = new Definition(
                shared: true,
                resolver: $resolver,
            );
            return;
        }

        $this->definitions[$id] = new Definition(
            shared: true,
            resolver: $resolver,
        );
    }

    /**
     * @param string $id
     * @param object $value
     * @return void
     */
    public function instance(string $id, object $value): void
    {
        $id = $this->normalize($id);
        $this->instances[$id] = $value;
        unset($this->definitions[$id]);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setParam(string $key, mixed $value): void
    {
        $this->params[$key] = $value;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasParam(string $key): bool
    {
        return array_key_exists($key, $this->params);
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * @param callable $callable
     * @param array<string, mixed> $overrides
     * @return mixed
     */
    public function call(callable $callable, array $overrides = []): mixed
    {
        $ref = $this->reflectCallable($callable);
        $args = [];

        foreach ($ref->getParameters() as $param) {
            $args[] = $this->resolveCallableParameter(
                $ref->getName(),
                $param,
                $overrides,
            );
        }

        return $callable(...$args);
    }

    /**
     * @param callable $callable
     * @return \ReflectionFunctionAbstract
     */
    private function reflectCallable(
        callable $callable,
    ): \ReflectionFunctionAbstract {
        if (is_array($callable)) {
            return new \ReflectionMethod($callable[0], $callable[1]);
        }

        if (is_object($callable) && method_exists($callable, '__invoke')) {
            return new \ReflectionMethod($callable, '__invoke');
        }

        return new \ReflectionFunction($callable);
    }

    /**
     * @param string $where
     * @param ReflectionParameter $param
     * @param array<string, mixed> $overrides
     * @return mixed
     */
    private function resolveCallableParameter(
        string $where,
        ReflectionParameter $param,
        array $overrides,
    ): mixed {
        $name = $param->getName();

        if (array_key_exists($name, $overrides)) {
            return $overrides[$name];
        }

        $type = $param->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $fqn = $type->getName();

            if (array_key_exists($fqn, $overrides)) {
                return $overrides[$fqn];
            }

            return $this->get($fqn);
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if ($type instanceof ReflectionNamedType && $type->allowsNull()) {
            return null;
        }

        throw new AutowireException("Cannot autowire {$where}::\${$name}.");
    }

    /**
     * @param string $id
     * @return object
     */
    private function resolve(string $id): object
    {
        if (isset($this->definitions[$id])) {
            $def = $this->definitions[$id];
            $obj = $this->executeResolver($def->resolver);

            if (!is_object($obj)) {
                throw new AutowireException(
                    "Resolver for {$id} did not return object.",
                );
            }

            if ($def->shared) {
                $this->instances[$id] = $obj;
            }

            return $obj;
        }

        if (!class_exists($id)) {
            throw new NotFoundException("No entry found for: {$id}");
        }

        $obj = $this->autowire($id);
        return $obj;
    }

    /**
     * @param Closure|string $resolver
     * @return mixed
     */
    private function executeResolver(Closure|string $resolver): mixed
    {
        if ($resolver instanceof Closure) {
            return $resolver($this);
        }

        return $this->autowire($resolver);
    }

    /**
     * @param string $class
     * @return object
     */
    private function autowire(string $class): object
    {
        $rc = new ReflectionClass($class);

        if (!$rc->isInstantiable()) {
            throw new AutowireException("Class {$class} is not instantiable.");
        }

        $ctor = $rc->getConstructor();
        if ($ctor === null) {
            return new $class();
        }

        $args = [];
        foreach ($ctor->getParameters() as $param) {
            $args[] = $this->resolveParameter($class, $param);
        }

        return $rc->newInstanceArgs($args);
    }

    /**
     * @param string $class
     * @param ReflectionParameter $param
     * @return mixed
     */
    private function resolveParameter(
        string $class,
        ReflectionParameter $param,
    ): mixed {
        $type = $param->getType();

        if ($type === null) {
            $key = $param->getName();
            if ($this->hasParam($key)) {
                return $this->param($key);
            }

            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }

            throw new AutowireException(
                "Cannot autowire {$class}::\${$param->getName()} (no type).",
            );
        }

        if (!$type instanceof ReflectionNamedType) {
            throw new AutowireException(
                "Cannot autowire {$class}::\${$param->getName()} (union/intersection not supported).",
            );
        }

        if ($type->isBuiltin()) {
            $keyByName = $param->getName();
            $keyByFqn = $class . ':' . $param->getName();

            if ($this->hasParam($keyByFqn)) {
                return $this->param($keyByFqn);
            }
            if ($this->hasParam($keyByName)) {
                return $this->param($keyByName);
            }

            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }

            if ($type->allowsNull()) {
                return null;
            }

            throw new AutowireException(
                "Cannot autowire scalar {$class}::\${$param->getName()} ({$type->getName()}). " .
                    "Provide param '{$keyByName}' or '{$keyByFqn}'.",
            );
        }

        $dep = $type->getName();
        return $this->get($dep);
    }

    /**
     * @param string $id
     * @return string
     */
    private function normalize(string $id): string
    {
        if (isset($this->aliases[$id])) {
            return $this->aliases[$id];
        }

        return $id;
    }
}
