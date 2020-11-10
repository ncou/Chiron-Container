<?php

declare(strict_types=1);

namespace Chiron\Container;

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
     * @var Container
     */
    protected $container;

    /**
     * Set the DI container.
     *
     * @param Container $container The DI container.
     *
     * @return self
     */
    public function setContainer(Container $container): ContainerAwareInterface
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Indicates if the container is defined.
     *
     * @return bool
     */
    public function hasContainer(): bool
    {
        return $this->container instanceof Container;
    }

    /**
     * Get the container instance. Only in a protected way, it's not necessary to be public.
     *
     * @throws UnexpectedValueException May be thrown if the container has not been set.
     *
     * @return Container
     */
    protected function getContainer(): Container
    {
        if ($this->hasContainer()) {
            return $this->container;
        }

        throw new UnexpectedValueException(sprintf('Container implementation not set in "%s".', static::class));
    }
}
