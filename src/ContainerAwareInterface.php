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
     * Sets a DI container instance on the object.
     *
     * @param Container $container The DI container.
     *
     * @return void
     */
    public function setContainer(ContainerInterface $container);
}
