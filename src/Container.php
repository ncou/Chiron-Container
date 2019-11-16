<?php

declare(strict_types=1);

namespace Chiron\Container;

use Chiron\Container\Exception\ContainerException;
use Chiron\Container\Exception\EntityNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use  Chiron\Container\Definition\DefinitionInterface;
use  Chiron\Container\Definition\Definition;
use  Chiron\Container\Inflector\Inflector;
use  Chiron\Container\Inflector\InflectorInterface;
use Chiron\Container\ServiceProvider\ServiceProviderInterface;
use Closure;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use LogicException;

// TODO : créer une méthode singleton() ou share() => https://github.com/illuminate/container/blob/master/Container.php#L354
// https://github.com/thephpleague/container/blob/master/src/Container.php#L92
//https://github.com/mrferos/di/blob/master/src/Container.php#L99

// TODO : améliorer le Circular exception avec le code : https://github.com/symfony/dependency-injection/blob/master/Container.php#L236

// TODO : passer la classe en final, et passer les fonctions protected en private.
class Container implements ContainerInterface, FactoryInterface
{
    /**
     * @var bool
     */
    protected $defaultToShared = false;

    /** @var \Chiron\Container\ContainerInterface */
    //public static $instance;

    /** @var \Chiron\Container\Definition[] */
    protected $definitions = [];

    /** @var \Chiron\Container\Inflector[] */
    protected $inflectors = [];

    /** @var array */
    protected $services = [];

    /** @var array */
    protected $aliases = [];

    /** @var ReflectionResolver */
    protected $resolver;

    /** @var array */
    protected $serviceProviders = [];

    public function __construct()
    {
        // TODO : créer une méthode getResolver() dans cette classe.
        $this->resolver = new ReflectionResolver($this);

        // TODO : ajouter un PHPunit pour vérifier si ces 4 classes sont bien ajoutées à la construction.
        // TODO : attention si on utilise ce bout de code, il faudra aussi faire une méthode __clone() qui remodifie ces valeurs d'instances. => https://github.com/Wandu/Framework/blob/master/src/Wandu/DI/Container.php#L65
        $this->share(Container::class, $this);
        $this->share(ContainerInterface::class, $this);
        $this->share(FactoryInterface::class, $this);
    }

    /**
     * Container can not be cloned.
     */
    public function __clone()
    {
        throw new LogicException('Container is not clonable');
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

    // TODO : améliorer le code, à quoi sert la vérification dans $this->services car si il existe une instance partagée dans ce tableau, elle est forcément présente dans le tableau précédent $this->definitions.
    // TODO : vérifier si l'ajout de la classe via $this->add() est vraiment nécessaire !!!!
    public function has($id)
    {
        if (! isset($this->definitions[$id]) && class_exists($id)) {
            $this->add($id);
        }

        return isset($this->definitions[$id]) || isset($this->services[$id]) || $this->isAlias($id);
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
     * Add multiple definitions at once.
     *
     * @param array $config definitions indexed by their ids
     */
    public function addDefinitions(array $config): void
    {
        foreach ($config as $id => $definition) {
            $this->add($id, $definition);
        }
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
    public function getDefinition(string $name): DefinitionInterface
    {
        $name = $this->getAlias($name);

        if (! array_key_exists($name, $this->definitions)) {
            throw new EntityNotFoundException("Service '$name' is not managed as a definition in the container");
        }

        return $this->definitions[$name];
    }

    /**
     * Allows for manipulation of specific types on resolution.
     *
     * @param string   $type     reprsent the class name
     * @param callable $callback
     *
     * @return InflectorInterface
     */
    public function inflector(string $type, callable $callback): InflectorInterface
    {
        return $this->inflectors[] = new Inflector($type, $callback);
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

        if (array_key_exists($name, $this->services) && $new === false) {
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

        $resolved = $this->resolver->resolve($definition->getConcrete(), $definition->getAssigns());
        //$instance = $this->resolver->resolve($definition->getConcrete(), $this->convertAssign($definition->assigns));

        $resolved = $this->inflect($resolved);

        if ($definition->isShared() && $new === false) {
            $this->services[$name] = $resolved;
        }

        return $resolved;
    }

    /**
     * Apply inflections to an object.
     *
     * @param object $object
     *
     * @return object
     */
    protected function inflect($object)
    {
        foreach ($this->inflectors as $inflector) {
            $type = $inflector->getType();

            if (! $object instanceof $type) {
                continue;
            }

            call_user_func($inflector->getCallback(), $object);
        }

        return $object;
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
            } else {
                if ($this->has($value)) {
                    $argumentsToReturn[$key] = $this->get($value);
                }
            }
        }

        return $argumentsToReturn;
    }

    /*******************************************************************************
     * Make new class
     ******************************************************************************/

    /**
     * {@inheritdoc}
     */
    // TODO : améliorer le code regarder ici   =>   https://github.com/illuminate/container/blob/master/Container.php#L778
    // TODO : améliorer le code et regarder ici => https://github.com/thephpleague/container/blob/68c148e932ef9959af371590940b4217549b5b65/src/Definition/Definition.php#L225
    // TODO : attention on ne gére pas les alias, alors que cela pourrait servir si on veut builder une classe en utilisant l'alias qui est présent dans le container. Réfléchir si ce cas peut arriver.
    // TODO : renommer en buildClass() ???? ou plutot en "make()" ????
    // TODO : améliorer le Circular exception avec le code : https://github.com/symfony/dependency-injection/blob/master/Container.php#L236
    public function build(string $className, array $arguments = [])
    {
        return $this->resolver->build($className, $arguments);
    }

    /*******************************************************************************
     * Service Provider
     ******************************************************************************/

    /**
     * Register a service provider with the application.
     *
     * @param ServiceProviderInterface|string $provider
     *
     * @return self
     */
    // TODO : améliorer le code : https://github.com/laravel/framework/blob/5.8/src/Illuminate/Foundation/Application.php#L594
    public function register($provider): self
    {
        $provider = $this->resolveProvider($provider);

        // don't process the service if it's already registered
        if (! $this->isProviderRegistered($provider)) {
            $this->registerProvider($provider);
        }

        return $this;
    }

    /**
     * Register a service provider with the application.
     *
     * @param ServiceProviderInterface|string $provider
     *
     * @return ServiceProviderInterface
     */
    protected function resolveProvider($provider): ServiceProviderInterface
    {
        // If the given "provider" is a string, we will resolve it.
        // This is simply a more convenient way of specifying your service provider classes.
        if (is_string($provider) && class_exists($provider)) {
            $provider = new $provider();
        }

        // TODO : voir si on garder ce throw car de toute facon le typehint va lever une exception.
        if (! $provider instanceof ServiceProviderInterface) {
            throw new InvalidArgumentException(
                sprintf('The provider must be an instance of "%s" or a valid class name.',
                    ServiceProviderInterface::class)
            );
        }

        return $provider;
    }

    protected function isProviderRegistered(ServiceProviderInterface $provider): bool
    {
        // is service already present in the array ? if it's the case, it's already registered.
        return array_key_exists(get_class($provider), $this->serviceProviders);
    }

    protected function registerProvider(ServiceProviderInterface $provider): void
    {
        $provider->register($this);
        // store the registered service
        $this->serviceProviders[get_class($provider)] = $provider;
    }

}
