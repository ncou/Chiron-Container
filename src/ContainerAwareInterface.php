<?php

declare(strict_types=1);

namespace Chiron\Container;

use Psr\Container\ContainerInterface;

/**
 * Defines the interface for a Container Aware class.
 * The "getContainer()" function is protected so we don't add it to the interface definition.
 */
interface ContainerAwareInterface
{
    /**
     * Set the DI container.
     *
     * @param Container $container The DI container.
     *
     * @return self
     */
    public function setContainer(Container $container): self;

    /**
     * Indicates if the container is defined.
     *
     * @return bool
     */
    public function hasContainer(): bool;
}
