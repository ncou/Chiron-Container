<?php

declare(strict_types=1);

namespace Chiron\Container\ServiceProvider;

use Chiron\Container\Container;

/**
 * Defines the interface for a Service Provider.
 */
interface ServiceProviderInterface
{
    /**
     * Boots services on the given container.
     *
     * @param Container $container A container instance
     */
    //public function boot(Container $container): void;

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $container A container instance
     */
    public function register(Container $container): void;
}
