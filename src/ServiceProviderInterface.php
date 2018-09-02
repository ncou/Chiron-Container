<?php

declare(strict_types=1);

namespace Chiron\Container;

use Psr\Container\ContainerInterface;

/**
 * Defines the interface for a Service Provider.
 */
interface ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param ContainerInterface $container A container instance
     */
    public function register(ContainerInterface $container): void;
}
