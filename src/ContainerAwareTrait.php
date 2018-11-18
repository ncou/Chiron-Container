<?php

declare(strict_types=1);

namespace Chiron\Container;

use Psr\Container\ContainerInterface;

/**
 * Defines the trait for a Container Aware Class.
 */
trait ContainerAwareTrait
{
    /**
     * The DI Container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * Set the DI container.
     *
     * @param Container $container The DI container.
     *
     */
    public function setContainer(ContainerInterface $container): ContainerAwareInterface
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get the DI container. Only in a protected way. It's not necessary to be public.
     *
     * @throws \UnexpectedValueException May be thrown if the container has not been set.
     *
     * @return Container
     */
    /*
    protected function getContainer(): ContainerInterface
    {
        if ($this->container) {
            return $this->container;
        }

        throw new \UnexpectedValueException('Container not set in ' . __CLASS__);
    }*/
}
