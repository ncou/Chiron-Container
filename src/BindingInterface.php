<?php

declare(strict_types=1);

namespace Chiron\Container;

use  Chiron\Container\Definition\DefinitionInterface;
use Psr\Container\ContainerInterface;
use  Chiron\Container\Inflector\InflectorInterface;

interface BindingInterface
{
    /**
     * @param string[] ...$names
     */
    //public function destroy(...$names);

    /**
     * @param string $className
     * @param mixed  $value
     *
     * @return DefinitionInterface
     */
    //public function instance(string $className, $value): DefinitionInterface;

    /**
     * @param string          $name
     * @param string|\Closure $className
     *
     * @return DefinitionInterface
     */
    //public function bind(string $name, $className = null): DefinitionInterface;

    /**
     * Whether the container should default to defining shared definitions.
     *
     * @param bool $shared
     *
     * @return self
     */
    public function defaultToShared(bool $shared = true): ContainerInterface;

    /**
     * Proxy to add with shared as true.
     *
     * @param string $id
     * @param mixed  $concrete
     *
     * @return \League\Container\Definition\DefinitionInterface
     */
    public function share(string $id, $concrete = null): DefinitionInterface;

    /**
     * Add multiple definitions at once.
     *
     * @param array $config definitions indexed by their ids
     */
    public function addDefinitions(array $config): void;

    /**
     * Add an item to the container.
     *
     * @param string $id
     * @param mixed  $concrete
     * @param bool   $shared
     *
     * @return \League\Container\Definition\DefinitionInterface
     */
    public function add(string $id, $concrete = null, bool $shared = null): DefinitionInterface;

    /**
     * @param string $alias
     * @param string $target
     */
    public function alias(string $alias, string $target);

    /**
     * @param string $name
     *
     * @return DefinitionInterface
     */
    public function getDefinition(string $name): DefinitionInterface;

    /**
     * Determine if a given string is an alias.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isAlias(string $name): bool;

    /**
     * Get the alias for an abstract if available.
     *
     * @param string $abstract
     *
     * @throws \LogicException
     *
     * @return string
     */
    public function getAlias(string $abstract): string;

    /**
     * Allows for manipulation of specific types on resolution.
     *
     * @param string   $type     reprsent the class name
     * @param callable $callback
     *
     * @return InflectorInterface
     */
    public function inflector(string $type, callable $callback): InflectorInterface;

    /**
     * Register a service provider with the application.
     *
     * @param ServiceProviderInterface|string $provider
     *
     * @return self
     */
    // TODO : améliorer le code : https://github.com/laravel/framework/blob/5.8/src/Illuminate/Foundation/Application.php#L594
    public function register($provider);

    /*
     * @param array $arguments
     *
     * @return \Wandu\DI\ContainerInterface
     */
    //public function with(array $arguments = []): ContainerInterface;

    /*
     * @param string $className
     * @param array  $arguments
     *
     * @return object
     */
    //public function build(string $className, array $arguments = []);

    /*
     * @param callable $callee
     * @param array    $arguments
     *
     * @return mixed
     */
    //public function call(callable $callee, array $arguments = []);

    /*
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param callable|string $callback
     * @param array           $parameters
     * @param string|null     $defaultMethod
     *
     * @return mixed
     */
    //public function call($callback, array $parameters = [], ?string $defaultMethod = null);
}
