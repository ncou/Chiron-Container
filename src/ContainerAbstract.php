<?php

declare(strict_types=1);

namespace Chiron\Container;

use Chiron\Container\Exception\ContainerException;
use Chiron\Container\Exception\EntityNotFoundException;
use Closure;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;

// TODO : créer une méthode singleton() ou share() => https://github.com/illuminate/container/blob/master/Container.php#L354
// https://github.com/thephpleague/container/blob/master/src/Container.php#L92
//https://github.com/mrferos/di/blob/master/src/Container.php#L99

// TODO : améliorer le Circular exception avec le code : https://github.com/symfony/dependency-injection/blob/master/Container.php#L236

class ContainerAbstract implements ContainerInterface
{
    /**
     * @var bool
     */
    protected $defaultToShared = false;

    /** @var \Chiron\Container\ContainerInterface */
    //public static $instance;

    /** @var \Chiron\Container\Definition[] */
    protected $definitions = [];

    /** @var array */
    protected $services = [];

    /** @var array */
    protected $classes = [];

    /** @var array */
    protected $closures = [];

    /** @var array */
    protected $aliases = [];

    protected $resolver;

    /**
     * Array of entries being resolved. Used to avoid circular dependencies and infinite loops.
     *
     * @var array
     */
    protected $entriesBeingResolved = [];

    public function __construct()
    {
        // TODO : créer une méthode getResolver() dans cette classe.
        $this->resolver = new ReflectionResolver($this);
    }

    /**
     * Whether the container should default to defining shared definitions.
     *
     * @param bool $shared
     *
     * @return self
     */
    public function defaultToShared(bool $shared = true): ContainerInterface
    {
        $this->defaultToShared = $shared;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name, bool $new = false)
    {
        return $this->resolve($name, $new);
    }

    /**
     * {@inheritdoc}
     */
    // TODO : mettre en place un systéme de cache dans le cas ou on fait un has() ca va instancier la classe il faudrait la mettre en cache pour éviter de devoir refaire la même chose si on doit faire un get() dans la foulée !!!
    /*
    public function has($name)
    {
        try {
            $this->resolve($name);

            return true;
            // TODO : améliorer le catch et lui attraper toute les exception de type PSR/Container/ContainerException
        } catch (ContainerExceptionInterface $e) {
        }

        return false;
    }*/

    public function has($id)
    {
        if (!isset($this->definitions[$id]) && class_exists($id)) {
            $this->add($id);
        }
        return isset($this->definitions[$id]) ||
                isset($this->services[$id]) ||
                $this->isAlias($id);
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
     * @param string $abstract
     *
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
    // TODO : à renommer en remove() ????
    // TODO : réfléchir si on conserve cette méthode

    public function destroy(...$names)
    {
        foreach ($names as $name) {
            unset(
                $this->definitions[$name],
                $this->services[$name],
                $this->classes[$name],
                $this->closures[$name]
                // TODO : il faudrait aussi supprimer l'alias !!!!!
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    // TODO : à virer c'est déprecated !!!!
    /*
    public function instance(string $name, $value): DefinitionInterface
    {
        $this->destroy($name);
        $this->instances[$name] = $value;

        return $this->definitions[$name] = new Definition($name, $value);
        //return $this->definitions[] = new Definition($name);
    }*/

    // TODO : renommer en invokable()
    /*
    public function closure(string $name, callable $handler): DefinitionInterface
    {
        return $this->bind($name, $handler);
    }*/

    /**
     * {@inheritdoc}
     */
    // TODO : lui passer un 3 eme paramétre pour savoir si c'est du shared ou non + créer une méthode share() => https://github.com/thephpleague/container/blob/master/src/Container.php#L92
    /*
    public function bind2(string $name, $className = null): DefinitionInterface
    {
        if (! isset($className)) {
            $this->destroy($name);
            $this->classes[$name] = $name;

            return $this->definitions[$name] = new Definition($name, $name);
            //return $this->definitions[] = new Definition($name);
        }
        if (is_string($className) && class_exists($className)) {
            $this->destroy($name, $className);
            $this->classes[$className] = $className;
            $this->alias($name, $className);

            return $this->definitions[$className] = new Definition($className, $className);
            //return $this->definitions[] = new Definition($className);
        } elseif (is_callable($className)) {
            $this->destroy($name);
            $this->closures[$name] = $className;

            return $this->definitions[$name] = new Definition($name, $className);
            //return $this->definitions[] = new Definition($name);
        }

        throw new ContainerException(
            sprintf('Argument 2 must be class name or Closure, "%s" given', is_object($className) ? get_class($className) : gettype($className))
        );
    }*/

    /**
     * Proxy to add with shared as true.
     *
     * @param string $id
     * @param mixed  $concrete
     *
     * @return \League\Container\Definition\DefinitionInterface
     */
    public function share(string $id, $concrete = null): DefinitionInterface
    {
        return $this->add($id, $concrete, true);
    }

    /**
     * Add an item to the container.
     *
     * @param string $id
     * @param mixed  $concrete
     * @param bool   $shared
     *
     * @return \League\Container\Definition\DefinitionInterface
     */
    public function add(string $id, $concrete = null, bool $shared = null): DefinitionInterface
    {
        // handle special case when the $id is the interface name and the $concrete the real class.
        // TODO : bout de code à virer si on recherche directement avec le getAlias du definition
        if (is_string($concrete) && class_exists($concrete)) {
            $this->alias($concrete, $id);
        }

        $concrete = $concrete ?? $id;
        $shared = $shared ?? $this->defaultToShared;

        if (! $concrete instanceof DefinitionInterface) {
            $concrete = new Definition($id, $concrete);
        }

        $this->definitions[$id] = $concrete
            ->setAlias($id)
            ->setShared($shared);

        return $concrete;
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
     * @param string $name
     *
     * @return bool
     */
    public function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]);
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
            throw new ContainerException("[{$abstract}] is aliased to itself.");
        }

        return $this->getAlias($this->aliases[$abstract]);
    }

    /**
     * {@inheritdoc}
     */
    // TODO : méthode à virer
    public function getDefinition(string $name): DefinitionInterface
    {
        $name = $this->getAlias($name);

        if (! array_key_exists($name, $this->definitions)) {
            throw new InvalidArgumentException($name);
        }

        return $this->definitions[$name];
    }

    /**
     * @param string $name
     *
     * @return mixed|object
     */
    protected function resolve($name, bool $new = false)
    {
        // resolve alias
        $name = $this->getAlias($name);

        // TODO : il faudrait aussi vérifier si $new est à false avant de rentrer dans ce test. non ????
        if (array_key_exists($name, $this->services)) {
            return $this->services[$name];
        }
        if (! array_key_exists($name, $this->definitions)) {
            if (! class_exists($name)) {
                throw new EntityNotFoundException("Service '$name' wasn't found in the dependency injection container");
            }
            $this->add($name);
        }

        $definition = $this->definitions[$name];
        //$definition = $this->getDefinition($name);

        $instance = $this->resolver->resolve($definition->getConcrete(), $definition->getAssigns());
        //$instance = $this->resolver->resolve($definition->getConcrete(), $this->convertAssign($definition->assigns));

        /*
        if (array_key_exists($name, $this->classes)) {
            //$instance = $this->resolver->build($this->classes[$name], $this->resolveArguments($definition->assigns));
            $instance = $this->resolver->resolve($this->classes[$name], $definition->assigns);
        } elseif (array_key_exists($name, $this->closures)) {
            //$instance = $this->resolver->call($this->closures[$name], $this->resolveArguments($definition->assigns));
            $instance = $this->resolver->resolve($this->closures[$name], $definition->assigns);
        }*/

        if ($definition->isShared() && $new === false) {
            $this->services[$name] = $instance;
        }

        return $instance;
    }

// TODO : méthode à virer !!!!
    protected function convertAssign(array $arguments): array
    {
        $argumentsToReturn = [];
        foreach ($arguments as $key => $value) {
            if (is_array($value)) {
                if (array_key_exists('value', $value)) {
                    $argumentsToReturn[$key] = $value['value'];
                }
                }
            else {
                if ($this->has($value)) {
                    $argumentsToReturn[$key] = $this->get($value);
                }
            }
        }

        return $argumentsToReturn;
    }
}
