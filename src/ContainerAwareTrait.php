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
     * DI Container
     *
     * @var    Container
     */
    private $container;

    /**
     * Set the DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  mixed  Returns itself to support chaining.
     *
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Get the DI container. Only in a protected way. It's not necessary to be public.
     *
     * @return  Container
     *
     * @throws  \UnexpectedValueException May be thrown if the container has not been set.
     */
    protected function getContainer(): ContainerInterface
    {
        if ($this->container)
        {
            return $this->container;
        }
        throw new \UnexpectedValueException('Container not set in ' . __CLASS__);
    }
}
