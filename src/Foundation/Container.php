<?php

declare(strict_types=1);

namespace Strux\Foundation;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use Strux\Component\Exceptions\Container\ContainerException;
use Strux\Component\Exceptions\Container\NotFoundException;
use Throwable;

/**
 * Class Container
 * A PSR-11 compliant dependency injection container with support for service lifecycles.
 */
class Container implements ContainerInterface
{
    private array $bindings = [];
    private array $instances = []; // For singleton and scoped instances

    /**
     * General purpose binding method.
     *
     * @param string $id The abstract identifier (e.g., Interface::class).
     * @param mixed $concrete The concrete implementation (Closure, class name string, or object).
     * @param string $lifecycle The service lifecycle ('singleton', 'transient', 'scoped').
     * @return void
     */
    public function bind(string $id, mixed $concrete, string $lifecycle = 'transient'): void
    {
        $this->bindings[$id] = compact('concrete', 'lifecycle');
        // If we are re-binding, we must clear any existing singleton instance.
        unset($this->instances[$id]);
    }

    /**
     * Binds a service that should only be resolved once (singleton).
     */
    public function singleton(string $id, mixed $concrete): void
    {
        $this->bind($id, $concrete, 'singleton');
    }

    /**
     * Binds a service that should be shared within its scope (alias for singleton in this implementation).
     */
    public function scoped(string $id, mixed $concrete): void
    {
        $this->bind($id, $concrete, 'singleton'); // Treating scoped as singleton
    }

    /**
     * Binds a service that will be resolved to a new instance each time.
     */
    public function transient(string $id, mixed $concrete): void
    {
        $this->bind($id, $concrete);
    }

    /**
     * Legacy set method, now acts as an alias for transient binding.
     * Your existing web/index.php uses this, so we keep it for now.
     * It's recommended to switch to singleton(), transient(), or scoped().
     */
    public function set(string $id, mixed $value): void
    {
        $this->bind($id, $value);
    }

    /**
     * Finds and returns an entry from the container by its identifier.
     */
    public function get(string $id): mixed
    {
        // For singletons/scoped, if we already have an instance, return it immediately.
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $concrete = $this->bindings[$id]['concrete'] ?? $id;
        $lifecycle = $this->bindings[$id]['lifecycle'] ?? 'transient';

        // If no definition is found but the ID is a valid, instantiable class name, auto-wire it.
        // Auto-wired classes are treated as transient by default unless bound otherwise.
        if (!isset($this->bindings[$id]) && !class_exists($id)) {
            throw new NotFoundException("No entry or class found for identifier '$id'");
        }

        // Resolve the entry. This could be a Closure, object, or class name string.
        try {
            $instance = $this->resolve($concrete);
        } catch (ContainerException|NotFoundException $e) {
            throw new RuntimeException("An error occurred: " . $e);
        }

        // If the service is a singleton/scoped, store the newly created instance for future requests.
        if ($lifecycle === 'singleton') {
            $this->instances[$id] = $instance;
        }

        return $instance;
    }

    /**
     * Resolves a concrete implementation into an object instance.
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function resolve(mixed $concrete): object|string|int|float|bool|array|null
    {
        if ($concrete instanceof Closure) {
            try {
                return $concrete($this);
            } catch (Throwable $e) {
                throw new ContainerException("Error resolving entry from Closure: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }

        if (is_string($concrete) && class_exists($concrete)) {
            return $this->autowire($concrete);
        }

        // If it's not a closure or a class string, return it as is (e.g., a pre-made object or etc value).
        return $concrete;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]) || class_exists($id);
    }

    /**
     * Attempts to automatically resolve a class and its dependencies via reflection.
     * @throws ContainerException
     * @throws NotFoundException
     */
    private function autowire(string $className): object
    {
        try {
            $reflectionClass = new ReflectionClass($className);
        } catch (ReflectionException $e) {
            throw new ContainerException("Failed to reflect class '$className': " . $e->getMessage(), 0, $e);
        }

        if (!$reflectionClass->isInstantiable()) {
            throw new ContainerException("Class '$className' is not instantiable.");
        }

        $constructor = $reflectionClass->getConstructor();
        if ($constructor === null) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependencies[] = $this->resolveParameter($parameter, $className);
        }

        try {
            return $reflectionClass->newInstanceArgs($dependencies);
        } catch (Throwable $e) {
            throw new ContainerException("Error during instantiation of '$className': " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Resolves a single constructor parameter.
     * @throws ContainerException|NotFoundException
     */
    private function resolveParameter(ReflectionParameter $parameter, string $classNameContext): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $typeName = $type->getName();
            if ($this->has($typeName)) {
                return $this->get($typeName);
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new ContainerException(
            "Cannot resolve constructor parameter '{$parameter->getName()}' for class '$classNameContext'."
        );
    }
}