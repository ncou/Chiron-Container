<?php

declare(strict_types=1);

namespace Chiron\Container;

// TODO : regarder ici pour récupérer la fonction alias / make / register : https://github.com/joomla-framework/di/blob/master/src/Container.php

//https://github.com/inxilpro/Zit/blob/master/lib/Zit/Container.php

// ALIAS :
//https://github.com/njasm/container

// PROTECT CLOSURE :
//https://github.com/frostealth/php-container/blob/master/src/container/Container.php#L105

// TODO : mettre en cache le container avec un serialize/unserialize : https://github.com/radarphp/Radar.Adr/blob/1.x/src/Boot.php#L79

// Delegate container :
// https://github.com/thecodingmachine/picotainer/blob/1.1/src/Picotainer.php

//*************
// TODO : ajouter la gestion des ServicesProviders => https://github.com/mnapoli/simplex/blob/master/src/Simplex/Container.php#L332
// Regarder aussi ici => https://github.com/thecodingmachine/slim-universal-module
// et ici pour les specs => https://github.com/container-interop/service-provider
//*************

// TODO : Exemple : moon-php/container  ou alors simplex

// TODO : gestion des exceptions : https://github.com/thephpleague/container/tree/master/src/Exception
// TODO : ou ici : https://github.com/ultra-lite/container/blob/master/src/UltraLite/Container/Exception/DiServiceNotFound.php
// TODO : https://github.com/slimphp/Slim/blob/477b00d96b9ace3c607ecd42a34750e280ab520c/Slim/Exception/ContainerException.php

// TODO : regarder comment engistrer des services comme dans simplex (methode extend et register) : https://github.com/mnapoli/simplex

use ArrayAccess;
use Chiron\Container\Exception\EntryNotFoundException;
use Closure;
use Psr\Container\ContainerInterface;
use SplObjectStorage;

class Container implements ArrayAccess, ContainerInterface
{
    /**
     * Contains all entries.
     *
     * @var array
     */
    private $container = [];

    private $factories;

    /**
     * Container constructor accept an array.
     * It must be an associative array with a 'alias' key and a 'entry' value.
     * The value can be anything: an integer, a string, a closure or an instance.
     *
     * @param array $entries
     */
    public function __construct(array $entries = [])
    {
        $this->factories = new SplObjectStorage();

        //$this->container = $entries;
        foreach ($entries as $alias => $entry) {
            $this->set($alias, $entry);
        }
    }

    /**
     * Sets a new alias.
     *
     * @param string $alias
     * @param string $entry
     */
    // TODO : on devrait pas faire une vérification si le service existe déjà (cad que l'alias est déjà utilisé) on léve une exception pour éviter d'acraser le service ???? ou alors il faudrait un paramétre pour forcer l'overwrite du service si il existe dejà
    // TODO : renommer la méthode en bind() ????
    public function set(string $alias, $entry)
    {
        // bind the "container" to the var "$this" inside the Closure function
        if ($entry instanceof Closure) {
            $entry = $entry->bindTo($this);
        }
        $this->container[$alias] = $entry;
    }

    /**
     * Unsets an alias.
     *
     * @param string $alias
     */
    public function remove(string $alias)
    {
        // TODO : tester si cela est utile (surement si on stocke un string au lieu d'un callable à voir)
        //if (is_object($this->container[$alias])) {
        unset($this->factories[$this->container[$alias]]);
        //}
        unset($this->container[$alias]);
    }

    /**
     * Marks a callable as being a factory service.
     *
     * @param callable $callable A service definition to be used as a factory
     *
     * @throws ExpectedInvokableException Service definition has to be a closure or an invokable object
     *
     * @return callable The passed callable
     */
    public function factory(callable $callable)
    {
        $this->factories->attach($callable);

        return $callable;
    }

    /**
     * {@inheritdoc}
     */
    public function get($alias)
    {
        //TODO : on devrait faire une vérif si le paramétre $alias est bien une string sinon on léve une exception !!!!!
        if (! $this->has($alias)) {
            throw new EntryNotFoundException($alias);
            //throw new \InvalidArgumentException("'$alias' doesn't exists in the Container component");
        }

        if (! is_callable($this->container[$alias])) {
            return $this->container[$alias];
        }

        $container = $this;

        if (isset($this->factories[$this->container[$alias]])) {
            return $this->container[$alias]($container);
        }

        $this->container[$alias] = $this->container[$alias]($container);

        return $this->container[$alias];
    }

    /**
     * {@inheritdoc}
     */
    public function has($alias)
    {
        //TODO : on devrait faire une vérif si le paramétre $alias est bien une string sinon on léve une exception !!!!!
        //return isset($this->container[$alias]);
        return array_key_exists($alias, $this->container);
    }

    // TODO : méthode à tester, et vérifier son utilité !!!!

    /**
     * Get container keys.
     *
     * @return array The container data keys
     */
    public function keys()
    {
        return array_keys($this->container);
    }

    /**
     * Registers a service provider.
     *
     * @param ServiceProviderInterface $provider A ServiceProviderInterface instance
     * @param array                    $values   An array of values that customizes the provider
     *
     * @return static
     */
    /*
    //https://github.com/silexphp/Pimple/blob/master/src/Pimple/Container.php#L288
    public function register(ServiceProviderInterface $provider, array $values = array())
    {
        $provider->register($this);
        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
        return $this;
    }
    */

    /**
     * Gets a parameter or an object.
     *
     * @param string $name The unique identifier for the parameter or object
     *
     * @throws EntryNotFoundException If the identifier is not defined
     *
     * @return mixed The value of the parameter or an object
     */
    public function offsetGet($name)
    {
        return $this->get($name);
    }

    /**
     * Sets a parameter or an object.
     *
     * Objects must be defined as Closures.
     *
     * Allowing any PHP callable leads to difficult to debug problems
     * as function names (strings) are callable (creating a function with
     * the same name as an existing parameter would break your container).
     *
     * @param string $name    The unique identifier for the parameter or object
     * @param mixed  $service The value of the parameter or a closure to define an object
     */
    public function offsetSet($name, $service)
    {
        $this->set($name, $service);
    }

    /**
     * Unsets a parameter or an object.
     *
     * @param string $name The unique identifier for the parameter or object
     */
    public function offsetUnset($name)
    {
        $this->remove($name);
    }

    /**
     * Checks if a parameter or an object is set.
     *
     * @param string $name The unique identifier for the parameter or object
     *
     * @return bool
     */
    public function offsetExists($name)
    {
        return $this->has($name);
    }

    /**
     * Returns a reference to various property arrays.
     *
     * @param string $name The property name to return.
     *
     * @throws EntryNotFoundException
     *
     * @return array
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    public function __set($name, $service)
    {
        $this->set($name, $service);
    }

    // TODO ; ajouter les méthodes isset et unset :
/*
    public function __isset($key) {
        return $this->has($key);
    }

    public function __unset($key) {
        return $this->remove($key);
    }
*/
}
