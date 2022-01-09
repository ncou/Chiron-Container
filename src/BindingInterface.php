<?php

declare(strict_types=1);

namespace Chiron\Container;

use Chiron\Container\Definition\Definition;
use Chiron\Container\Mutation\MutationInterface;

// TODO : nettoyer les méthodes non utilisées
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
     * @return Definition
     */
    //public function instance(string $className, $value): Definition;

    /**
     * @param string          $name
     * @param string|\Closure $className
     *
     * @return Definition
     */
    //public function bind(string $name, $className = null): Definition;

    /**
     * Whether the container should default to defining shared definitions.
     *
     * @param bool $shared
     *
     * @return self
     */
    //public function defaultToShared(bool $shared = true): ContainerInterface;

    /**
     * Proxy to add with shared as true.
     *
     * @param string $id
     * @param mixed  $concrete
     *
     * @return Definition
     */
    public function singleton(string $id, mixed $concrete = null): Definition;

    /**
     * Add multiple definitions at once.
     *
     * @param array $config definitions indexed by their ids
     */
    //public function addDefinitions(array $config): void;

    /**
     * Add an item to the container.
     *
     * @param string    $id
     * @param mixed     $concrete
     * @param bool|null $shared
     *
     * @return Definition
     */
    public function bind(string $id, mixed $concrete = null, ?bool $shared = null): Definition;

    /**
     * @param string $alias
     * @param string $target
     *
     * @return Definition
     */
    public function alias(string $alias, string $target): Definition;

    /**
     * Get a definition to extend.
     *
     * @param string $id
     *
     * @return Definition
     */
    //public function extend(string $id): Definition;

    public function bound(string $id): bool;

    public function remove(string $id): void;

    /**
     * Determine if a given string is an alias.
     *
     * @param string $name
     *
     * @return bool
     */
    //public function isAlias(string $name): bool;

    /**
     * Get the alias for an abstract if available.
     *
     * @param string $abstract
     *
     * @throws \LogicException
     *
     * @return string
     */
    //public function getAlias(string $abstract): string;

    /**
     * Allows for manipulation of specific types on resolution.
     *
     * @param string   $type     represent the class name
     * @param callable $callback
     *
     * @return MutationInterface
     */
    public function mutation(string $type, callable $callback): MutationInterface;

    /**
     * Register a service provider with the application.
     *
     * @param ServiceProviderInterface|string $provider
     *
     * @return self
     */
    // TODO : améliorer le code : https://github.com/laravel/framework/blob/5.8/src/Illuminate/Foundation/Application.php#L594
    //public function register($provider);

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
