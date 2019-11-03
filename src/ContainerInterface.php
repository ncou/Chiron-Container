<?php

declare(strict_types=1);

namespace Chiron\Container;

use  Chiron\Container\Definition\DefinitionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
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
