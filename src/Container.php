<?php

declare(strict_types=1);

namespace Chiron\Container;

use Chiron\Container\Exception\ContainerException;
use Chiron\Container\Exception\EntityNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Chiron\Container\Definition\DefinitionInterface;
use Chiron\Container\Definition\Definition;
use Chiron\Container\Inflector\Inflector;
use Chiron\Container\Inflector\InflectorInterface;
use Chiron\Container\ServiceProvider\ServiceProviderInterface;
use Closure;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use LogicException;

// TODO : remonter la classe Reference à la racine du Container car ce n'est pas une définition !!!!! Eventuellement la déplacer dans un répertoire "Argument" ou "Support"
use Chiron\Container\Definition\Reference;

// TODO : créer une méthode singleton() ou share() => https://github.com/illuminate/container/blob/master/Container.php#L354
// https://github.com/thephpleague/container/blob/master/src/Container.php#L92
//https://github.com/mrferos/di/blob/master/src/Container.php#L99

// TODO : améliorer le Circular exception avec le code : https://github.com/symfony/dependency-injection/blob/master/Container.php#L236

// TODO : passer la classe en final, et passer les fonctions protected en private.
// TODO : ajouter une méthode "removeBinding($serviceName)" et la rajouter dans la classe BindingInterface  => https://github.com/spiral/core/blob/master/src/Container.php#L354

/**
 * Container implements a [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container.
 */
// TODO : lui ajouter un InvokerInterface + ajouter la méthode call (ou invoker) et make (ou build) en public directement dans cette classe Container::class
class Container implements ContainerInterface, BindingInterface, FactoryInterface
{
    /**
     * The current globally available container (if any).
     *
     * @var static
     */
    public static $instance;

    /**
     * @var bool
     */
    protected $defaultToShared = false;

    /** @var \Chiron\Container\Definition[] */
    protected $definitions = [];

    /** @var \Chiron\Container\Inflector[] */
    protected $inflectors = [];

    /** @var array */
    protected $services = [];

    /** @var array */
    //protected $aliases = [];

    /** @var ReflectionResolver */
    protected $resolver;

    public function __construct()
    {
        // TODO : créer une méthode getResolver() dans cette classe.
        $this->resolver = new ReflectionResolver($this);

        // TODO : ajouter un PHPunit pour vérifier si ces 4 classes sont bien ajoutées à la construction.
        // TODO : attention si on utilise ce bout de code, il faudra aussi faire une méthode __clone() qui remodifie ces valeurs d'instances. => https://github.com/Wandu/Framework/blob/master/src/Wandu/DI/Container.php#L65
        $this->share(Container::class, $this);
        $this->share(ContainerInterface::class, $this);
        $this->share(FactoryInterface::class, $this);
        $this->share(BindingInterface::class, $this);

        // TODO : ajouter un binding sur FactoryInterface et sur InvokerInterface
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
     * Allows for manipulation of specific types on resolution.
     *
     * @param string   $type     represent the class name
     * @param callable $callback
     *
     * @return InflectorInterface
     */
    public function inflector(string $type, callable $callback): InflectorInterface
    {
        return $this->inflectors[] = new Inflector($type, $callback);
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

    // TODO : on devrait pas retourner un DefinitionInterface ? cad faire un return du singleton() ????
    public function alias(string $alias, string $target): void
    {
        $this->share($alias, Reference::to($target));
    }

    /**
     * Proxy to add with shared as true.
     *
     * @param string $id
     * @param mixed  $concrete
     *
     * @return \League\Container\Definition\DefinitionInterface
     */
    // TODO : méthode à renommer en "singleton()"
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
     * Get a definition to extend.
     *
     * @param string $name
     *
     * @return DefinitionInterface
     */
    public function extend(string $name): DefinitionInterface
    {
        //$name = $this->getAlias($name);

        if (! array_key_exists($name, $this->definitions)) {
            throw new EntityNotFoundException("Service '$name' is not managed as a definition in the container");
        }

        return $this->definitions[$name];
    }

    // TODO : renommer la méthode en bound()
    public function isBinded(string $id): bool
    {
        return isset($this->definitions[$id]);
        //return array_key_exists($id, $this->definitions);
    }

    // TODO : renommer en removeBinding() ????
    public function unbind(string $id)
    {
        unset($this->definitions[$id]);
    }


    // TODO : améliorer le code, à quoi sert la vérification dans $this->services car si il existe une instance partagée dans ce tableau, elle est forcément présente dans le tableau précédent $this->definitions.
    // TODO : vérifier si l'ajout de la classe via $this->add() est vraiment nécessaire !!!!
    // TODO : faire uniquement un test sur class_exist($id) || isset($this->definitions[$id])
    public function has($id)
    {
        /*
        if (! isset($this->definitions[$id]) && class_exists($id)) {
            $this->add($id);
        }

        return isset($this->definitions[$id]);// || isset($this->services[$id]);// || $this->isAlias($id);
        */

        return $this->isBinded($id) || class_exists($id);
    }

    /**
     * {@inheritdoc}
     */
    // return mixed
    public function get($name, bool $new = false)
    {
        // resolve alias
        //$name = $this->getAlias($name);

        if (array_key_exists($name, $this->services) && $new === false) {
            return $this->services[$name];
        }

        // handle the case when you want to resolve a classname not already binded in the container.
        if (! array_key_exists($name, $this->definitions)) {
            if (! class_exists($name)) {
                // TODO : utiliser la fonction levenshtein pour afficher les services le plus proche du nom du service recherché ?     https://github.com/symfony/dependency-injection/blob/b4f099e65175874bd326ec9a86d6df57a217a6a4/Container.php#L268
                throw new EntityNotFoundException("Service '$name' wasn't found in the dependency injection container");
            }
            // if the class to build in an instanceof SingletonInterface, we force the share parameter at true.
            $share = is_subclass_of($name, SingletonInterface::class) ? true : null;
            $this->add($name, null, $share);
        }

        $definition = $this->definitions[$name];
        //$definition = $this->getDefinition($name);

        $resolved = $this->resolver->resolveDefinition($definition);
        //$resolved = $this->resolver->resolve($definition->getConcrete(), $definition->getAssigns());
        //$instance = $this->resolver->resolve($definition->getConcrete(), $this->convertAssign($definition->assigns));

        $resolved = $this->inflect($resolved);

        // singleton
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
    /*
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
    }*/

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
    // TODO : renommmer la fonction en make() et ajouter ce nom de fonction dans l'interface FactoryInterface
    public function build(string $className, array $arguments = [])
    {
        //return $this->resolver->resolve($className, $arguments);
        return $this->resolver->build($className, $arguments);
    }

    /*******************************************************************************
     * Singleton
     ******************************************************************************/
    /**
     * Get the globally available instance of the container.
     *
     * @return static
     */
    // TODO : cette méthode devrait renvoyer null dans le cas ou l'instance n'est pas créée => on ne doit pas faire un new static() dans cette fonction !!!!!
    /*
    public static function getInstance(): Container
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }*/
    /**
     * Set the shared instance of the container.
     *
     * @param \Chiron\Container\Container|null $container
     *
     * @return \Chiron\Container\Container|static
     */
    // TODO : vérifier si on conserve cette méthode ?????
    /*
    public static function setInstance(Container $container = null)
    {
        // TODO : forcer le type de retour dans la signature de la méthode, et vérifier ce qui se passe si on ne passe rien si le "null" est retourné par cette méthode.
        return static::$instance = $container;
    }*/

    /**
     * Initialise the instance with the $this value, and return the previous instance (or null on the first call)
     *
     * @return null|static previous instance
     */
    public function setAsGlobal(): ?self
    {
        $previous = static::$instance;
        static::$instance = $this;

        return $previous;
    }

}
