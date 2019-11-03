<?php

declare(strict_types=1);

namespace Chiron\Container\Definition;

interface DefinitionInterface
{
    /**
     * Set the alias of the definition.
     *
     * @param string $id
     */
    public function setAlias(string $id): DefinitionInterface;

    /**
     * Get the alias of the definition.
     *
     * @return string
     */
    public function getAlias(): string;

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
}
