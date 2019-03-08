<?php

declare(strict_types=1);

namespace Chiron\Container;

use Doctrine\Common\Annotations\Reader;
use LogicException;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunctionAbstract;
use Closure;
use Chiron\Container\Annotations\Alias;
use Chiron\Container\Annotations\Assign;
use Chiron\Container\Annotations\AssignValue;
use Chiron\Container\Annotations\Factory;
use Chiron\Container\Annotations\Wire;
use Chiron\Container\Annotations\WireValue;
use Chiron\Container\Exception\CannotChangeException;
use Chiron\Container\Exception\CannotFindParameterException;
use Chiron\Container\Exception\DependencyException;
use Chiron\Container\Exception\CannotResolveException;
use Chiron\Container\Exception\NullReferenceException;
use Chiron\Container\Reflection\ReflectionCallable;

// TODO : créer une méthode singleton() ou share() => https://github.com/illuminate/container/blob/master/Container.php#L354
// https://github.com/thephpleague/container/blob/master/src/Container.php#L92
//https://github.com/mrferos/di/blob/master/src/Container.php#L99

// TODO : améliorer le Circular exception avec le code : https://github.com/symfony/dependency-injection/blob/master/Container.php#L236

class Container implements ContainerInterface
{
    /** @var \Wandu\DI\ContainerInterface */
    //public static $instance;

    /** @var \Wandu\DI\Descriptor[] */
    protected $descriptors = [];

    /** @var array */
    // TODO : renommer ce tableau en "services[]" ????
    protected $instances = [];

    /** @var array */
    protected $classes = [];

    /** @var array */
    protected $closures = [];

    /** @var array */
    protected $aliases = [];

    /**
     * Array of entries being resolved. Used to avoid circular dependencies and infinite loops.
     * @var array
     */
    protected $entriesBeingResolved = [];

    public function __construct(array $options = [])
    {
        // TODO : à virer
        $this->instances = [
            Container::class => $this,
            ContainerInterface::class => $this,
            PsrContainerInterface::class => $this,
            'container' => $this,
        ];
        $this->descriptors[Container::class]
            = $this->descriptors[ContainerInterface::class]
            = $this->descriptors[PsrContainerInterface::class]
            = $this->descriptors['container']
            = (new Descriptor())->freeze();
    }

    // TODO : à virer
    public function __clone()
    {
        $this->instances[Container::class] = $this;
        $this->instances[ContainerInterface::class] = $this;
        $this->instances[PsrContainerInterface::class] = $this;
        $this->instances['container'] = $this;
    }

    /**
     * @return \Wandu\DI\ContainerInterface
     */
    /*
    public function setAsGlobal()
    {
        $instance = static::$instance;
        static::$instance = $this;
        return $instance;
    }*/

    /**
     * {@inheritdoc}
     */
    /*
    public function __call($name, array $arguments)
    {
        return $this->call($this->get($name), $arguments);
    }*/

    /**
     * {@inheritdoc}
     */
    public function offsetExists($name)
    {
        return $this->has($name) && $this->get($name) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($name)
    {
        return $this->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($name, $value)
    {
        $this->instance($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($name)
    {
        $this->destroy($name);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (isset($this->descriptors[$name])) {
            $this->descriptors[$name]->freeze();
        }
        return $this->resolve($name);
    }

    // TODO : à virer c'est pour faire un raccourci vers la fonction ->instance()
    public function set($name, $value)
    {
        $this->instance($name, $value);
    }

    /**
     * Wrap the given closure such that its dependencies will be injected when executed.
     *
     * @param  \Closure  $callback
     * @param  array  $parameters
     * @return \Closure
     */
    // https://github.com/illuminate/container/blob/master/Container.php#L556
    // TODO : le paramétre $callback ne devrait pas plutot être du type callable au lieu de Closure ?????
    public function wrap(Closure $callback, array $parameters = []): Closure
    {
        return function () use ($callback, $parameters) {
            return $this->call($callback, $parameters);
        };
    }

    /**
     * Get a closure to resolve the given type from the container.
     *
     * @param  string  $abstract
     * @return \Closure
     */
    //https://github.com/illuminate/container/blob/master/Container.php#L582
    public function factory(string $abstract): Closure
    {
        return function () use ($abstract) {
            // this will resolve the item (so instanciate class or execute closure)
            return $this->get($abstract);
        };
    }

    /**
     * {@inheritdoc}
     */
    // TODO : mettre en place un systéme de cache dans le cas ou on fait un has() ca va instancier la classe il faudrait la mettre en cache pour éviter de devoir refaire la même chose si on doit faire un get() dans la foulée !!!
    public function has($name)
    {
        try {
            $this->resolve($name);
            return true;
            // TODO : améliorer le catch et lui attraper toute les exception de type PSR/Container/ContainerException
        } catch (NullReferenceException $e) {
        } catch (CannotResolveException $e) {
        } catch (DependencyException $e) {
        }
        return false;
    }

/*
// TODO : regarder si on peut utiliser cette méthode pour tester le has() !!!!
    public function has($name): bool
    {
        //TODO : on devrait faire une vérif si le paramétre $alias est bien une string sinon on léve une exception !!!!!
        return array_key_exists($name, $this->services) || $this->isAlias($name);
    }
*/

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param  string  $abstract
     * @return bool
     */
    /*
    //https://github.com/illuminate/container/blob/master/Container.php#L158
    public function bound($abstract)
    {
        return isset($this->bindings[$abstract]) ||
               isset($this->instances[$abstract]) ||
               $this->isAlias($abstract);
    }*/
    /**
     *  {@inheritdoc}
     */
    /*
    public function has($id)
    {
        return $this->bound($id);
    }*/



    /**
     * {@inheritdoc}
     */
    public function destroy(...$names)
    {
        foreach ($names as $name) {
            if (array_key_exists($name, $this->descriptors)) {
                if ($this->descriptors[$name]->frozen) {
                    throw new CannotChangeException($name);
                }
            }
            unset(
                $this->descriptors[$name],
                $this->instances[$name],
                $this->classes[$name],
                $this->closures[$name]
                // TODO : il faudrait aussi supprimer l'alias !!!!!
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function instance(string $name, $value): DescriptorInterface
    {
        $this->destroy($name);
        $this->instances[$name] = $value;
        return $this->descriptors[$name] = new Descriptor();
    }

    /**
     * @deprecated
     */
    public function closure(string $name, callable $handler): DescriptorInterface
    {
        return $this->bind($name, $handler);
    }

    /**
     * {@inheritdoc}
     */
    // TODO : lui passer un 3 eme paramétre pour savoir si c'est du shared ou non + créer une méthode share() => https://github.com/thephpleague/container/blob/master/src/Container.php#L92
    public function bind(string $name, $className = null): DescriptorInterface
    {
        if (!isset($className)) {
            $this->destroy($name);
            $this->classes[$name] = $name;
            return $this->descriptors[$name] = new Descriptor();
        }
        if (is_string($className) && class_exists($className)) {
            $this->destroy($name, $className);
            $this->classes[$className] = $className;
            $this->alias($name, $className);
            return $this->descriptors[$className] = new Descriptor();
        } elseif (is_callable($className)) {
            $this->destroy($name);
            $this->closures[$name] = $className;
            return $this->descriptors[$name] = new Descriptor();
        }
        throw new InvalidArgumentException(
            sprintf('Argument 2 must be class name or Closure, "%s" given', is_object($className) ? get_class($className) : gettype($className))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function alias(string $alias, string $target): void
    {
        $this->aliases[$alias] = $target;
    }

    /**
     * Determine if a given string is an alias.
     *
     * @param  string  $name
     * @return bool
     */
    public function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]);
    }

    /**
     * Get the alias for an abstract if available.
     *
     * @param  string  $abstract
     * @return string
     *
     * @throws \LogicException
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

    /**
     * {@inheritdoc}
     */
    public function descriptor(string $name): DescriptorInterface
    {
        $name = $this->getAlias($name);

        if (!array_key_exists($name, $this->descriptors)) {
            throw new NullReferenceException($name);
        }
        return $this->descriptors[$name];
    }

    /**
     * {@inheritdoc}
     */
    // TODO : éviter de faire un clone et renvoyer $this
    public function with(array $arguments = []): ContainerInterface
    {
        $new = clone $this;
        foreach ($arguments as $name => $argument) {
            $new->instance($name, $argument);
        }
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    // TODO : améliorer le code regarder ici   =>   https://github.com/illuminate/container/blob/master/Container.php#L778
    // TODO : améliorer le code et regarder ici => https://github.com/thephpleague/container/blob/68c148e932ef9959af371590940b4217549b5b65/src/Definition/Definition.php#L225
    // TODO : attention on ne gére pas les alias, alors que cela pourrait servir si on veut builder une classe en utilisant l'alias qui est présent dans le container. Réfléchir si ce cas peut arriver.
    // TODO : renommer en buildClass() ????
    // TODO : améliorer le Circular exception avec le code : https://github.com/symfony/dependency-injection/blob/master/Container.php#L236
    public function build(string $className, array $arguments = [])
    {
        if (! class_exists($className)) {
            throw new NullReferenceException($className);
        }

        try {
            // TODO : vérifier que le constructeur est public !!!! => https://github.com/PHP-DI/PHP-DI/blob/cdcf21d2a8a60605e81ec269342d48b544d0dfc7/src/Definition/Source/ReflectionBasedAutowiring.php#L31
            // Constructor
            $class = new ReflectionClass($className);

            // Prevent error if you try to instanciate an abstract class or a class with a private constructor.
            if (! $class->isInstantiable()) {
                throw new DependencyException(sprintf(
                    'Entry "%s" cannot be resolved: the class is not instantiable',
                    $className
                ));
            }

            // Check if we are already getting this entry -> circular dependency
            if (isset($this->entriesBeingResolved[$className])) {
                throw new DependencyException(sprintf(
                    'Circular dependency detected while trying to resolve entry "%s"',
                    $className
                ));
            }
            $this->entriesBeingResolved[$className] = true;

            // TODO : améliorer ce bout de code, on fait 2 fois un new class, alors qu'on pourrait en faire qu'un !!! https://github.com/illuminate/container/blob/master/Container.php#L815
            if ($constructor = $class->getConstructor()) {
                $arguments = $this->getParameters($constructor, $arguments);

                unset($this->entriesBeingResolved[$className]);

                return new $className(...$arguments);
            }
        } catch (CannotFindParameterException $e) {
            throw new CannotResolveException($className, $e->getParameter());
        }

        unset($this->entriesBeingResolved[$className]);

        //$reflection->newInstanceArgs($resolved);
        return new $className;
    }

    /**
     * {@inheritdoc}
     */
    // TODO : regarder ici le code qui permet d'executer aussi les callables ajoutés dans le container   => https://github.com/mrferos/di/blob/master/src/Container.php#L173
    // grosso modo le typehint de $callee peut être une string présente dans le tableau des closure =>  $this->closure[$calleeName]
    // TODO : méthode à virer !!!!
    public function call_old(callable $callee, array $arguments = [])
    {
        try {
            return call_user_func_array(
                $callee,
                $this->getParameters(new ReflectionCallable($callee), $arguments)
            );
        } catch (CannotFindParameterException $e) {
            throw new CannotResolveException($callee, $e->getParameter());
        }
    }




    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string  $callback
     * @param  array  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     */
    public function call($callback, array $parameters = [], ?string $defaultMethod = null)
    {
        if ($this->isCallableWithAtSign($callback) || $defaultMethod) {
            return $this->callClass($callback, $parameters, $defaultMethod);
        }

        /*
        return $this->callBoundMethod($container, $callback, function () use ($container, $callback, $parameters) {
            return call_user_func_array(
                $callback, $this->getMethodDependencies($container, $callback, $parameters)
            );
        });*/

        if (! is_callable($callback)) {
            throw new InvalidArgumentException(sprintf(
                '(%s) is not resolvable.',
                is_array($callback) || is_object($callback) || is_null($callback) ? json_encode($callback) : $callback
            ));
        }


        try {
            return call_user_func_array(
                $callback,
                $this->getParameters(new ReflectionCallable($callback), $parameters)
            );
        } catch (CannotFindParameterException $e) {
            throw new CannotResolveException($callback, $e->getParameter());
        }
    }

    /**
     * Call a string reference to a class using Class@method syntax.
     *
     * @param  string  $target
     * @param  array  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    private function callClass(string $target, array $parameters = [], ?string $defaultMethod = null)
    {
        $segments = explode('@', $target);
        // We will assume an @ sign is used to delimit the class name from the method
        // name. We will split on this @ sign and then build a callable array that
        // we can pass right back into the "call" method for dependency binding.
        $method = count($segments) === 2 ? $segments[1] : $defaultMethod;

        if (is_null($method)) {
            throw new InvalidArgumentException('Method not provided.');
        }

        return $this->call([$this->get($segments[0]), $method], $parameters);
    }


    /**
     * Determine if the given string is in Class@method syntax.
     *
     * @param  mixed  $callback
     * @return bool
     */
    private function isCallableWithAtSign($callback): bool
    {
        return is_string($callback) && strpos($callback, '@') !== false;
    }




















    /**
     * @param string $name
     * @return mixed|object
     */
    protected function resolve($name)
    {
        // resolve alias
        $name = $this->getAlias($name);

        if (array_key_exists($name, $this->instances)) {
            return $this->instances[$name];
        }
        if (!array_key_exists($name, $this->descriptors)) {
            if (!class_exists($name)) {
                throw new NullReferenceException($name);
            }
            $this->bind($name);
        }
        $descriptor = $this->descriptors[$name];
        if (array_key_exists($name, $this->classes)) {
            $instance = $this->build($this->classes[$name], $this->resolveArguments($descriptor->assigns));
        } elseif (array_key_exists($name, $this->closures)) {
            $instance = $this->call($this->closures[$name], $this->resolveArguments($descriptor->assigns));
        }

        // TODO : à virer dans la classe description et à virer d'ici.
        foreach ($descriptor->afterHandlers as $handler) {
            $this->call($handler, [$instance]);
        }
        foreach ($this->resolveArguments($descriptor->wires) as $propertyName => $value) {
            $refl = (new \ReflectionObject($instance))->getProperty($propertyName);
            $refl->setAccessible(true);
            $refl->setValue($instance, $value);
        }

        if (!$descriptor->factory) {
            $this->instances[$name] = $instance;
        }

        return $instance;
    }

    /**
     * @param array $arguments
     * @return array
     */
    // TODO : méthode à déplacer dans la classe Descriptor.
    protected function resolveArguments(array $arguments): array
    {
        $argumentsToReturn = [];
        foreach ($arguments as $key => $value) {
            if (is_array($value)) {
                if (array_key_exists('value', $value)) {
                    $argumentsToReturn[$key] = $value['value'];
                }
            } else {
                try {
                    $argumentsToReturn[$key] = $this->get($value);
                } catch (NullReferenceException $e) {}
            }
        }
        return $argumentsToReturn;
    }

    /**
     * @param \ReflectionFunctionAbstract $reflection
     * @param array $arguments
     * @return array
     */
    // TODO : renommer en getMethodDependencies()
    protected function getParameters(ReflectionFunctionAbstract $reflection, array $arguments = []): array
    {
        // TODO : améliorer ce bout de code ******************
        $parametersToReturn = static::getSeqArray($arguments); // utiliser plutot ce bout de code pour éviter d'initialiser un tableau lorsque les clés sont numeriques => https://github.com/illuminate/container/blob/master/BoundMethod.php#L119

        $reflectionParameters = array_slice($reflection->getParameters(), count($parametersToReturn));

        if (!count($reflectionParameters)) {
            return $parametersToReturn;
        }
        // TODO END ******************************************

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
                            // TODO : on devrait pas créer une méthode make() qui soit un alias de get ? => https://github.com/illuminate/container/blob/master/Container.php#L616
                            // TODO : https://github.com/illuminate/container/blob/master/Container.php#L925
                            // TODO : ajouter des tests dans le cas ou la classe passée en parameter est optionnelle (cad avec une valeur par défaut), il faudrait aussi faire un test avec "?ClassObject" voir si on passe null par défaut ou si on léve une exception car la classe n'existe pas !!!! => https://github.com/illuminate/container/blob/master/Container.php#L935
                            $parametersToReturn[] = $this->get($paramClassName);
                            continue;
                        } catch (NullReferenceException $e) {}
                    }
                }
                if ($param->isDefaultValueAvailable()) { // #3.
                    $parametersToReturn[] = $param->getDefaultValue();
                    continue;
                }

                throw new CannotFindParameterException($paramName); // #4.
            } catch (ReflectionException $e) {
                // ReflectionException is thrown when the class doesn't exist.
                throw new CannotFindParameterException($paramName);
            }
        }
        return $parametersToReturn;
    }

    /**
     * @param array $array
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
