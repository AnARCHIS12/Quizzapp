<?php

declare(strict_types=1);

namespace App\Core;

use Exception;
use ReflectionClass;

/**
 * Dependency Injection Container
 */
class Container
{
    private array $bindings = [];
    private array $instances = [];

    /**
     * Bind a service/interface to a resolver
     */
    public function bind(string $key, $resolver): void
    {
        $this->bindings[$key] = $resolver;
    }

    /**
     * Bind a singleton instance
     */
    public function singleton(string $key, $instance): void
    {
        $this->instances[$key] = $instance;
    }

    /**
     * Resolve and build dependency
     */
    public function get(string $key)
    {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        if (isset($this->bindings[$key])) {
            $resolver = $this->bindings[$key];
            if (is_callable($resolver)) {
                $instance = $resolver($this);
            } else {
                $instance = $this->build($resolver);
            }
            return $instance;
        }

        return $this->build($key);
    }

    /**
     * Auto-wire a class using reflection
     */
    public function build(string $className)
    {
        if (!class_exists($className)) {
            throw new Exception("Class {$className} does not exist in DI Container.");
        }

        $reflector = new ReflectionClass($className);
        if (!$reflector->isInstantiable()) {
            throw new Exception("Class {$className} is not instantiable.");
        }

        $constructor = $reflector->getConstructor();
        if (null === $constructor) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            if (null === $type) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("Cannot resolve parameter {$parameter->getName()} in class {$className}.");
                }
            } else {
                // Get the type name
                $typeName = $type->getName();
                if ($typeName === 'App\Core\Container') {
                    $dependencies[] = $this;
                } else {
                    $dependencies[] = $this->get($typeName);
                }
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
