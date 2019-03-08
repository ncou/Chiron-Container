<?php

declare(strict_types=1);

namespace Chiron\Container;

use ArrayAccess;
use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends ArrayAccess, PsrContainerInterface
{
    /**
     * @param string[] ...$names
     * @return void
     */
    public function destroy(...$names);

    /**
     * @param string $className
     * @param mixed $value
     * @return DescriptorInterface
     */
    public function instance(string $className, $value): DescriptorInterface;

    /**
     * @param string $name
     * @param string|\Closure $className
     * @return DescriptorInterface
     */
    public function bind(string $name, $className = null): DescriptorInterface;

    /**
     * @param string $alias
     * @param string $target
     * @return void
     */
    public function alias(string $alias, string $target);

    /**
     * @param string $name
     * @return DescriptorInterface
     */
    public function descriptor(string $name): DescriptorInterface;

    /**
     * @param array $arguments
     * @return \Wandu\DI\ContainerInterface
     */
    public function with(array $arguments = []): ContainerInterface;

    /**
     * @param string $className
     * @param array $arguments
     * @return object
     */
    public function build(string $className, array $arguments = []);

    /**
     * @param callable $callee
     * @param array $arguments
     * @return mixed
     */
    //public function call(callable $callee, array $arguments = []);

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string  $callback
     * @param  array  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     */
    public function call($callback, array $parameters = [], ?string $defaultMethod = null);
}
