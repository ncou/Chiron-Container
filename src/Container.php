<?php

declare(strict_types=1);

namespace Chiron\Container;

//https://github.com/illuminate/container/blob/master/Container.php#L569

// TODO : regarder ici pour récupérer la fonction alias / make / register : https://github.com/joomla-framework/di/blob/master/src/Container.php
// TODO : gestion du "call()" ou "make()" qui retrouve automatiquement les paramétres de la fonction par rapport à ce qu'il y a dans le container :
//https://github.com/Wandu/Framework/blob/master/src/Wandu/DI/Container.php#L279
//https://github.com/illuminate/container/blob/master/Container.php#L569    +   https://github.com/laravel/framework/blob/e0dbd6ab143286d81bedf2b34f8820f3d49ea15f/src/Illuminate/Foundation/Application.php#L795
//https://github.com/illuminate/container/blob/master/BoundMethod.php

// TODO : regarder ici pour la méthode delegate et defaultToShared => https://github.com/thephpleague/container/blob/master/src/Container.php#L104

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
use LogicException;
use Psr\Container\ContainerInterface;
use SplObjectStorage;

class Container implements ContainerInterface, ArrayAccess
{
    /**
     * Contains all entries.
     *
     * @var array
     */
    protected $services = [];

    /**
     * The registered type aliases.
     *
     * @var array
     */
    protected $aliases = [];

    protected $factories;

    /**
     * Container constructor accept an array.
     * It must be an associative array with a 'name' key and a 'entry' value.
     * The value can be anything: an integer, a string, a closure or an instance.
     *
     * @param array $entries
     */
    // TODO : il faudrait surement faire une méthode __clone() qui dupliquera l'objet factories !!!!! non ?????
    public function __construct(array $entries = [])
    {
        $this->factories = new SplObjectStorage();

        //$this->services = $entries;
        foreach ($entries as $name => $entry) {
            $this->set($name, $entry);
        }
    }

    /**
     * Sets a new service.
     *
     * @param string $name
     * @param mixed  $entry
     */
    // TODO : on devrait pas faire une vérification si le service existe déjà (cad que le nom est déjà utilisé) on léve une exception pour éviter d'acraser le service ???? ou alors il faudrait un paramétre pour forcer l'overwrite du service si il existe dejà
    // TODO : renommer la méthode en bind() ????
    public function set(string $name, $entry)
    {
        // bind the "container" to the var "$this" inside the Closure function
        if ($entry instanceof Closure) {
            $entry = $entry->bindTo($this);
        }
        $this->services[$name] = $entry;
    }

    /**
     * Unsets a service.
     *
     * @param string $name
     */
    public function remove(string $name)
    {
        // TODO : tester si cela est utile (surement si on stocke un string au lieu d'un callable à voir)
        //if (is_object($this->services[$name])) {
        unset($this->factories[$this->services[$name]]);
        //}
        unset($this->services[$name]);
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
    public function get($name)
    {
        $name = $this->getAlias($name);

        //TODO : on devrait faire une vérif si le paramétre $name est bien une string sinon on léve une exception !!!!!
        if (! $this->has($name)) {
            throw new EntryNotFoundException($name);
            //throw new \InvalidArgumentException("'$name' doesn't exists in the Container component");
        }

        if (! is_callable($this->services[$name])) {
            return $this->services[$name];
        }

        $container = $this;

        if (isset($this->factories[$this->services[$name]])) {
            return $this->services[$name]($container);
        }

        $this->services[$name] = $this->services[$name]($container);

        return $this->services[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function has($name): bool
    {
        //TODO : on devrait faire une vérif si le paramétre $alias est bien une string sinon on léve une exception !!!!!
        //return isset($this->services[$alias]);
        return array_key_exists($name, $this->services) || $this->isAlias($name);
    }

    /**
     * Determine if a given string is an alias.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]);
    }

    /**
     * Alias a type to a different name.
     *
     * @param string $alias
     * @param string $target
     */
    public function alias(string $alias, string $target): void
    {
        $this->aliases[$alias] = $target;
    }

    /**
     * Get the alias for an abstract if available.
     *
     * @param string $abstract
     *
     * @throws \LogicException
     *
     * @return string
     */
    public function getAlias(string $abstract): string
    {
        if (! isset($this->aliases[$abstract])) {
            return $abstract;
        }
        if ($this->aliases[$abstract] === $abstract) {
            throw new LogicException("[{$abstract}] is aliased to itself.");
        }

        return $this->getAlias($this->aliases[$abstract]);
    }

    // TODO : méthode à tester, et vérifier son utilité !!!!

    /**
     * Get container keys.
     *
     * @return array The container data keys
     */
    public function keys()
    {
        return array_keys($this->services);
    }

    /**
     * Flush the container of all services and aliases.
     */
    public function flush()
    {
        $this->services = [];
        $this->aliases = [];
        $this->factories = new SplObjectStorage();
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
     * @param string $name  The unique identifier for the parameter or object
     * @param mixed  $entry The value of the parameter or a closure to define an object
     */
    public function offsetSet($name, $entry)
    {
        $this->set($name, $entry);
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
     * Dynamically access container services.
     *
     * @param string $name
     *
     * @return mixed
     */
    // TODO : réfléchir si on doit pas virer cette méthode.
    public function __get($name)
    {
        return $this[$name];
    }

    /**
     * Dynamically set container services.
     *
     * @param string $name
     * @param mixed  $value
     */
    // TODO : réfléchir si on doit pas virer cette méthode.
    public function __set($name, $entry)
    {
        $this[$name] = $entry;
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

    //https://github.com/Wandu/Framework/blob/master/src/Wandu/DI/Container.php#L279
    /**
     * {@inheritdoc}
     */
    /*
    public function call(callable $callee, array $arguments = [])
    {
        try {
            return call_user_func_array(
                $callee,
                $this->getParameters(new ReflectionCallable($callee), $arguments)
            );
        } catch (CannotFindParameterException $e) {
            throw new CannotResolveException($callee, $e->getParameter());
        }
    }*/

    public function call(callable $callee, array $arguments = [])
    {
        return call_user_func_array(
            $callee,
            $this->getParameters(new ReflectionCallable($callee), $arguments)
        );
    }

    /**
     * @param \ReflectionFunctionAbstract $reflectionFunction
     * @param array                       $arguments
     *
     * @return array
     */
    protected function getParameters(\ReflectionFunctionAbstract $reflectionFunction, array $arguments = []): array
    {
        $parametersToReturn = static::getSeqArray($arguments);
        $reflectionParameters = array_slice($reflectionFunction->getParameters(), count($parametersToReturn));
        if (! count($reflectionParameters)) {
            return $parametersToReturn;
        }
        /* @var \ReflectionParameter $param */
        foreach ($reflectionParameters as $param) {
            /*
             * #1. search in arguments by parameter name
             * #1.1. search in arguments by class name
             * #2. if parameter has type hint
             * #2.1. search in container by class name
             * #3. if has default value, insert default value.
             * #4. exception
             */
            $paramName = $param->getName();

            try {
                if (array_key_exists($paramName, $arguments)) { // #1.
                    $parametersToReturn[] = $arguments[$paramName];

                    continue;
                }
                $paramClass = $param->getClass();
                if ($paramClass) { // #2.
                    $paramClassName = $paramClass->getName();
                    if (array_key_exists($paramClassName, $arguments)) {
                        $parametersToReturn[] = $arguments[$paramClassName];

                        continue;
                    } else { // #2.1.
                        try {
                            $parametersToReturn[] = $this->get($paramClassName);

                            continue;
                        } catch (\Psr\Container\NotFoundExceptionInterface $e) {
                        }
                    }
                }
                if ($param->isDefaultValueAvailable()) { // #3.
                    $parametersToReturn[] = $param->getDefaultValue();

                    continue;
                }

                throw new \RuntimeException("cannot find parameter \"{$paramName}\"."); // #4.
            } catch (\ReflectionException $e) {
                throw new \RuntimeException("cannot find parameter \"{$paramName}\".");
            }
        }

        return $parametersToReturn;
    }

    /**
     * @param array $array
     *
     * @return array
     */
    protected static function getSeqArray(array $array): array
    {
        $arrayToReturn = [];
        foreach ($array as $key => $item) {
            if (is_int($key)) {
                $arrayToReturn[] = $item;
            }
        }

        return $arrayToReturn;
    }
}
