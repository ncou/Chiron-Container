<?php

declare(strict_types=1);

namespace Chiron\Container;

use Psr\Container\ContainerInterface;
use UnexpectedValueException;

//https://github.com/thephpleague/container/blob/master/src/ContainerAwareTrait.php

/**
 * Defines the trait for a Container Aware Class.
 */
trait ContainerAwareTrait
{
    /**
     * DI Container.
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Set the DI container.
     *
     * @param ContainerInterface $container The DI container.
     *
     * @return mixed Returns itself to support chaining.
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get the container instance. Only in a protected way, it's not necessary to be public.
     *
     * @throws UnexpectedValueException May be thrown if the container has not been set.
     *
     * @return Container
     */
    protected function getContainer(): ContainerInterface
    {
        if ($this->container) {
            return $this->container;
        }

        throw new UnexpectedValueException('Container not set in ' . __CLASS__);
    }
}
