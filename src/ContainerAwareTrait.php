<?php

declare(strict_types=1);

namespace Chiron\Container;

use UnexpectedValueException;

//https://github.com/thephpleague/container/blob/master/src/ContainerAwareTrait.php
//https://github.com/thephpleague/container/blob/4.x/src/ContainerAwareTrait.php

/**
 * Defines the trait for a Container Aware Class.
 */
// TODO : déplacer cette classe+l'interface dans le package chiron/core ???? cela permettrait aussi d'utiliser une exception ImproperlyConfiguredException dans le cas ou le container n'est pas là !!!
trait ContainerAwareTrait
{
    /** @var ?Container */
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
        // TODO : lever une exception si on n'a pas implémenté l'interface ContainerAwareInterface car le return $this sera en conflit avec le return typehint !!!
        //https://github.com/thephpleague/container/blob/4.x/src/ContainerAwareTrait.php

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

        // TODO : retourner plutot une ContainerException ???? ou ce n'est pas logique ?
        // TODO : faire un throw new MissingContainerException('Container is missing, use setContainer() method to set it.');
        throw new UnexpectedValueException(sprintf('Container implementation not set in "%s".', static::class));
    }
}
