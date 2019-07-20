<?php

declare(strict_types=1);

namespace Chiron\Container;

use ArrayAccess;
use Chiron\Container\Exception\ContainerException;
use Chiron\Container\ServiceProvider\ServiceProviderInterface;
use Closure;
use InvalidArgumentException;

// TODO : créer une méthode singleton() ou share() => https://github.com/illuminate/container/blob/master/Container.php#L354
// https://github.com/thephpleague/container/blob/master/src/Container.php#L92
//https://github.com/mrferos/di/blob/master/src/Container.php#L99

// TODO : améliorer le Circular exception avec le code : https://github.com/symfony/dependency-injection/blob/master/Container.php#L236

//TODO : Classe de TESTS pour les méthode register : https://github.com/laravel/framework/blob/master/tests/Foundation/FoundationApplicationTest.php

class Container extends ReflectionContainer implements ArrayAccess
{
    /**
     * The current globally available kernel (if any).
     *
     * @var static
     */
    protected static $instance;

    /**
     * Indicates if the kernel has "booted".
     *
     * @var bool
     */
    protected $isBooted = false;

    /**
     * All of the registered service providers.
     *
     * @var array
     */
    protected $serviceProviders = [];

    public function __construct()
    {
        parent::__construct();

        // TODO : attention si on utilise ce bout de code, il faudra aussi faire une méthode __clone() qui remodifie ces valeurs d'instances. => https://github.com/Wandu/Framework/blob/master/src/Wandu/DI/Container.php#L65
        $this->share(Container::class, $this);
    }

    /*******************************************************************************
     * Helpers
     ******************************************************************************/

    /**
     * Wrap the given closure such that its dependencies will be injected when executed.
     *
     * @param \Closure $callback
     * @param array    $parameters
     *
     * @return \Closure
     */
    // https://github.com/illuminate/container/blob/master/Container.php#L556
    // TODO : le paramétre $callback ne devrait pas plutot être du type callable au lieu de Closure ????? / voir même de type string car le call support les string avec le signe @
    public function wrap(Closure $closure, array $parameters = []): Closure
    {
        return function () use ($closure, $parameters) {
            return $this->call($closure, $parameters);
        };
    }

    /**
     * Get a closure to resolve the given type from the container.
     *
     * @param string $abstract
     *
     * @return \Closure
     */
    //https://github.com/illuminate/container/blob/master/Container.php#L582
    public function factory(string $abstract): Closure
    {
        return function () use ($abstract) {
            // this will resolve the item (so instanciate class or execute closure)
            return $this->get($abstract, true);
        };
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

    /*******************************************************************************
     * Build() new class or Call() callable
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

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param callable|string $callback
     * @param array           $parameters
     * @param string|null     $defaultMethod
     *
     * @return mixed
     */
    // TODO : améliorer le cas ou on passe uniquement une string sans le @__invoke qui est sous entendu !!! https://github.com/laravel/lumen-framework/blob/1a0855a5187af7bf3db1697cce05242dfb9271ea/src/Concerns/RoutesRequests.php#L305
    public function call($callback, array $parameters = [], ?string $defaultMethod = null)
    {
        /*
        if (is_string($callback) && strpos($callback, '@') === false) {
            $callback .= '@__invoke';
        }*/
        
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

        return $this->resolver->call($callback, $parameters);
    }

    /**
     * Call a string reference to a class using Class@method syntax.
     *
     * @param string      $target
     * @param array       $parameters
     * @param string|null $defaultMethod
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
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
     * @param mixed $callback
     *
     * @return bool
     */
    private function isCallableWithAtSign($callback): bool
    {
        return is_string($callback) && strpos($callback, '@') !== false;
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

            // If the application has already booted, we will call this boot method on
            // the provider class so it has an opportunity to do its boot logic and
            // will be ready for any usage by this developer's application logic.
            if ($this->isBooted) {
                $this->bootProvider($provider);
            }
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

    /**
     * Boot the application's service providers.
     */
    public function boot(): self
    {
        if (! $this->isBooted) {
            foreach ($this->serviceProviders as $provider) {
                $this->bootProvider($provider);
            }
            $this->isBooted = true;
        }

        return $this;
    }

    /**
     * Boot the given service provider.
     *
     * @param \Illuminate\Support\ServiceProvider $provider
     *
     * @return mixed
     */
    protected function bootProvider(ServiceProviderInterface $provider): void
    {
        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }
    }

    /*******************************************************************************
     * Singleton
     ******************************************************************************/

    /**
     * Set the globally available instance of the container.
     *
     * @return \Chiron\Container\Container|static
     */
    public static function getInstance(): Container
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param \Chiron\Container\Container|null $container
     *
     * @return \Chiron\Container\Container|static
     */
    public static function setInstance(Container $container = null)
    {
        // TODO : forcer le type de retour dans la signature de la méthode, et vérifier ce qui se passe si on ne passe rien si le "null" est retourné par cette méthode.
        return static::$instance = $container;
    }

    /*******************************************************************************
     * Array Access
     ******************************************************************************/

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
        $this->add($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($name)
    {
        $this->destroy($name);
    }
}
