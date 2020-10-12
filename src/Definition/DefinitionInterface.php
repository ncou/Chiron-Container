<?php

declare(strict_types=1);

namespace Chiron\Container\Definition;

use Chiron\Container\Container;

interface DefinitionInterface
{
    /**
     * Set the name of the definition.
     *
     * @param string $name
     */
    public function setName(string $name): DefinitionInterface;

    /**
     * Get the name of the definition.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set whether this is a shared definition.
     *
     * @param bool $shared
     *
     * @return self
     */
    public function setShared(bool $shared): DefinitionInterface;

    /**
     * Is this a shared definition?
     *
     * @return bool
     */
    public function isShared(): bool;

    /**
     * Get the concrete of the definition.
     *
     * @return mixed
     */
    public function getConcrete();

    /**
     * Set the concrete of the definition.
     *
     * @param mixed $concrete
     *
     * @return DefinitionInterface
     */
    public function setConcrete($concrete): DefinitionInterface;

    /**
     * Add an argument to be injected.
     *
     * @param mixed $arg
     *
     * @return self
     */
    public function addArgument($arg): DefinitionInterface;

    /**
     * Add multiple arguments to be injected.
     *
     * @param array $args
     *
     * @return self
     */
    public function addArguments(array $args): DefinitionInterface;

     /**
     * Resolve the concrete using the container.
     *
     * @param Container $container
     * @param bool      $new
     *
     * @return mixed
     */
    public function resolve(Container $container, bool $new);
}
