<?php

declare(strict_types=1);

namespace Chiron\Container;

use Chiron\Container\Exception\ContainerException;
use Chiron\Container\Exception\EntityNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Chiron\Container\Definition\Definition;
use Chiron\Container\Inflector\Inflector;
use Chiron\Container\Inflector\InflectorInterface;
use Chiron\Container\ServiceProvider\ServiceProviderInterface;
use Closure;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use LogicException;

use Chiron\Injector\Injector;

//https://github.com/symfony/symfony/blob/4dd6e2f0b2daefc2bddd08aa056370afb1c1cb1d/src/Symfony/Component/DependencyInjection/Container.php#L236
//https://github.com/symfony/symfony/blob/master/src/Symfony/Component/DependencyInjection/Container.php#L309

//https://github.com/illuminate/container/blob/master/Container.php

// TODO : créer une méthode singleton() ou share() => https://github.com/illuminate/container/blob/master/Container.php#L354
// https://github.com/thephpleague/container/blob/master/src/Container.php#L92
//https://github.com/mrferos/di/blob/master/src/Container.php#L99


//https://github.com/spiral/core/blob/master/src/Container.php
//https://github.com/thephpleague/container/blob/master/src/Container.php
//https://github.com/yiisoft/di/blob/master/src/Container.php


// TODO : améliorer le Circular exception avec le code : https://github.com/symfony/dependency-injection/blob/master/Container.php#L236

// TODO : ajouter une méthode "removeBinding($serviceName)" et la rajouter dans la classe BindingInterface  => https://github.com/spiral/core/blob/master/src/Container.php#L354

/**
 * Container implements a [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container.
 */
final class Container implements ContainerInterface, BindingInterface, FactoryInterface, InvokerInterface
{
    /** @var static */
    // TODO : faire une méthode getInstance (qui retournerai l'instance du container ou null si on n'a pas appellé la méthode setAsGlobal) ???? ca éviterai d'avoir une propriété public dans la classe. On pourrait même lever une exception si le container n'est pas initialisé et qu'on fait l'appel à la méthode getInstance !!!!
    public static $instance;

    /** @var Injector */
    private $injector;

    /** @var \Chiron\Container\Definition[] */
    // TODO : on devrait pas renommer ce tableau en "bindings" ?
    private $definitions = [];

    /** @var \Chiron\Container\Inflector[] */
    private $inflectors = [];

    /** @var array */
    private $entriesBeingResolved = [];

    /** @var bool */
    private $defaultToShared = false;

    /**
     * Container constructor.
     */
    // TODO : lui passer en paramétre un bool '$asGlobal' initialisé par défaut à false, et qui permet d'appeller la méthode ->setAsGlobal() dans ce constructeur.
    public function __construct()
    {
        $this->injector = new Injector($this);

        // TODO : ajouter un PHPunit pour vérifier si ces 4 classes sont bien ajoutées à la construction.
        $this->singleton(Container::class, $this);
        $this->singleton(ContainerInterface::class, $this);
        $this->singleton(BindingInterface::class, $this);
        $this->singleton(FactoryInterface::class, $this);
        $this->singleton(InvokerInterface::class, $this);
    }

    /**
     * Container can not be cloned.
     */
    public function __clone()
    {
        // TODO : il faudrait pas lever une ContainerException plutot ????
        throw new LogicException('Container is not clonable');
    }

    /**
     * Whether the container should default to defining shared definitions.
     *
     * @param bool $shared
     *
     * @return self
     */
    public function defaultToShared(bool $shared = true): self
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
    // TODO : renommer en mutation ou en interceptor ? avec une méthode qui s'execute soit respectivement "mutate()" ou "proceed()"
    public function inflector(string $type, callable $callback): InflectorInterface
    {
        return $this->inflectors[] = new Inflector($type, $callback);
    }

    /**
     * Apply mutations to an object. If no mutation found, return original object.
     *
     * @param mixed $target The object to mutate (if it's not an object typehint the parameter is not modified).
     *
     * @return mixed The mutated object or the original object if no mutation found (or if the input is not an object)
     */
    // TODO : passer la version miniame de PHP à 7.3 car on est en train d'utiliser le typehint "object" qui est introduit seulement depuis PHP7.3
    // TODO : améliorer le code : https://github.com/auraphp/Aura.Di/blob/4.x/src/Resolver/Blueprint.php#L114
    // TODO : déplacer cette méthode dans la classe Definition pour améliorer le resolve() et surtout faire une méthode public Container::getMutations() qui servira à récupérer $this->inflectors[]
    public function mutate($target)
    {
        foreach ($this->inflectors as $inflector) {
            $type = $inflector->getType();

            if (! $target instanceof $type) {
                continue;
            }

            // TODO : il faudrait pas que l'on stocke le retour de cette appel dans $target, pour gérer le cas des objets immuables. Et il faudrait dans ce cas revérifier que le type de retour est bien toujours le même type d'object qu'on a recu en entrée.
            call_user_func($inflector->getCallback(), $target);
        }

        // TODO : il faudrait pas faire une vérification que l'instance mutée est bien du même type d'objet que celui qu'on a eu en entrée de la fonction ? cela évitera que la mutation retourne un autre type d'objet, ce n'est pas le but de la mutation !!!!
        return $target;
    }




    public function alias(string $alias, string $target): Definition
    {
        return $this->bind($alias, Reference::to($target), false);
    }

    /**
     * Proxy to bind with shared as true.
     *
     * @param string $name
     * @param mixed  $concrete
     *
     * @return Definition
     */
    public function singleton(string $name, $concrete = null): Definition
    {
        return $this->bind($name, $concrete, true);
    }

    /**
     * Register a binding with the container.
     *
     * @param string $name
     * @param mixed  $concrete
     * @param bool   $shared
     *
     * @return Definition
     */
    // TODO : ajouter une vérif (et lever une erreur) sur le binding d'un service qui est déjà initialisé : https://github.com/symfony/symfony/blob/4dd6e2f0b2daefc2bddd08aa056370afb1c1cb1d/src/Symfony/Component/DependencyInjection/Container.php#L172           +                   https://github.com/symfony/symfony/blob/4dd6e2f0b2daefc2bddd08aa056370afb1c1cb1d/src/Symfony/Component/DependencyInjection/Container.php#L293
    // TODO : lever une erreur si on essaye de rebinder un service qui est de type "Privé" ou éventuellement utiliser plutot un attribut "freezed" dans la classe Definition pour indiquer qu'on ne peut pas redéfinir(cad rebinder) ce service !!!!
    // TODO : vérifier le type de concréte qu'on veut binder. exemple : https://github.com/yiisoft/di/blob/master/src/Container.php#L168
    public function bind(string $name, $concrete = null, bool $shared = null): Definition
    {
        // handle special case when the $name is the interface name and the $concrete the real class.
        // TODO : bout de code à virer si on vérifie que les string qui ne sont pas des noms de classes lévent une erreur.
        if (is_string($concrete) && class_exists($concrete)) {
            // Attention il faut améliorer ce bout de code car en passant par la méthode 'alias()' cela force la valeur du shared à false, ce qui n'est peut etre pas le comportement attentu si l'utilisateur a mis une valeur au paramétre $shared lorsqu'il a appellé cette méthode bind() !!!!!
            $this->alias($concrete, $name);
        }

        $concrete = $concrete ?? $name;
        $shared = $shared ?? $this->defaultToShared;

        // TODO : virer ce cas là !!!
        if (! $concrete instanceof Definition) {
            $concrete = new Definition($name, $concrete);
        }

        $this->definitions[$name] = $concrete
            ->setId($name)
            ->setShared($shared);

        return $concrete;
    }

    /**
     * {@inheritdoc}
     */
    // TODO : lever une exception si le paramétre $name n'est pas de type string !!!!
    // TODO : externaliser ce bout de code dans une méthode privée, qui bind si besoin l'objet dans le container. exemple de nom : assertBinding()
    // TODO : externaliser dans une méthode nommée "autowire", mais qui se charge de retourner directement l'instance en faisant un appel à la méthode "make" => https://github.com/spiral/core/blob/86ffeac422f2f368a890ccab71cf6a8b20668176/src/Container.php#L145
    // TODO : exemple en faisant 2 fois la vérification sur le isBound === true         https://github.com/slince/di/blob/master/Container.php#L197
    // handle the case when you want to resolve a classname not already binded in the container.
    public function get($name, bool $new = false)
    {
        if (! $this->bound($name)) {
            //No direct instructions how to construct class, bind a new definition.
            $this->autowire($name);
        }

        return $this->resolve($name, $new);
    }

    // TODO : appeller la méthode make() dans ce cas là ????
    private function autowire(string $className): void
    {
        // TODO : il faudrait faire un test d'enlever cette vérification que la classe existe, car même si on bind une classe qui n'existe pas il doit bien il y avoir un test plus tard pour vérifier ce cas là !!!! Ou alors cela va retourner une simple chaine de caractéres ???? faire le test de ce cas là ca sera plus simple...
        if (! class_exists($className)) {
            // TODO : utiliser la fonction levenshtein pour afficher les services le plus proche du nom du service recherché ?     https://github.com/symfony/dependency-injection/blob/b4f099e65175874bd326ec9a86d6df57a217a6a4/Container.php#L268
            throw new EntityNotFoundException(sprintf('Service "%s" wasn\'t found in the dependency injection container.', $className));
        }

        // TODO : faire plutot un if/else sur $className is_a($className, SingletonInterface::class, true) et si c'est vrai on appel $this->singleton(), else $this->bind(), le code sera plus lisible de cette maniére !!!
        // if the class to build in an instanceof SingletonInterface, we force the share parameter at true.
        $share = is_subclass_of($className, SingletonInterface::class) ? true : null;
        $this->bind($className, null, $share);
    }

    /**
     * Resolve the definition and apply mutations if needed.
     *
     * @param string $name
     * @param bool $new
     *
     * @return mixed The resolved and mutated object
     */
    private function resolve(string $name, bool $new)
    {
        $definition = $this->definitions[$name];
        // TODO : ce bout de code ne devrait plus servir car quand on va virer la méthode setId() de la classe définition, la variable $name sera toujours la même que la valeur du getId() donc on pourra virer $entry et utiliser à la place $name !!!!
        $entry = $definition->getId();

        // TODO : améliorer la gestion des exceptions circulaires en affichant dans l'exception l'ensemble des classes initialisée précédemment comme ca on retrouvera l'origine de l'appel (cad la 1ere classe qu'on essaye de résoudre via le get !!!!)
        // Check if we are already getting this entry -> circular dependency
        if (isset($this->entriesBeingResolved[$entry])) {
            // TODO : créer une DependencyException ????
            throw new ContainerException(sprintf(
                'Circular dependency detected while trying to resolve entry "%s"',
                $entry
            ));
        }
        $this->entriesBeingResolved[$entry] = true;

        try {
            // resolve and apply mutations.
            $resolved = $definition->resolve($this, $new);
        } finally {
            unset($this->entriesBeingResolved[$entry]);
        }

        return $resolved;
    }

    /**
     * {@inheritdoc}
     */
    public function has($name): bool
    {
        return $this->bound($name) || class_exists($name);
    }

    /**
     * Get a definition to extend.
     *
     * @param string $name
     *
     * @return Definition
     */
    public function extend(string $name): Definition
    {
        if (! $this->bound($name)) {
            throw new EntityNotFoundException("Service '$name' is not managed as a definition in the container");
        }

        return $this->definitions[$name];
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param string $abstract
     *
     * @return bool
     */
    public function bound(string $name): bool
    {
        return isset($this->definitions[$name]);
        //return array_key_exists($name, $this->definitions);
    }

    // TODO : utilité de la méthode ????
    // TODO : renommer en removeBinding() ???? ou plutot en remove() et ca se chargerai de supprimer un binding, un singleton ou un alias car c'est tout dans le même tableau "$this->definitions"
    // TODO : retourner un booléen si le unbinding s'est bien passé ? ou alors rester sur un void ?
    // TODO : on devrait pas faire un test "isbound === true" avant de faire le unset ? et lever une exception si on essaye d'enlever un service qui n'est pas bindé !!!
    public function remove(string $name): void
    {
        unset($this->definitions[$name]);
    }







    // TODO : méthode à virer !!!!
    /*
    private function convertAssign(array $arguments): array
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
    // return mixed
    public function build(string $className, array $arguments = [])
    {
        // TODO : il faudrait pas convertir les exceptions remontées par cette fonction "build" en ContainerException ???? faire un try/catch et change le type de l'exception ????
        // TODO : il faudrait vérifier que le $className est bien une classe qui existe !!!! is_class()
        // TODO : il faudra appliquer les mutations enregistrées pour cette classe une fois qu'on a fait le build. $this->mutate($instance);
        // TODO : il faudra surement améliorer le message des références circulaires, car on il faudra indiquer que le point d'entrée est la tentative de résolution de $className
        return $this->injector->build($className, $arguments);

    }

    // return mixed
    // TODO : ne surtout PAS forcer le typehint à callable car il serait possible de faire un CallableResolver avant, non ????
    public function invoke(callable $callable, array $arguments = [])
    {
        // TODO : il faudra surement améliorer le message des références circulaires, car on il faudra indiquer que le point d'entrée est la tentative de résolution de $callable
        // TODO : il faudrait pas convertir les exceptions remontées par cette fonction "build" en ContainerException ???? faire un try/catch et change le type de l'exception ????
        return $this->injector->invoke($callable, $arguments);
    }

    // Méthode à virer c'est pour faire tourner les tests.
    public function call(callable $callable, array $arguments = [])
    {
        return $this->invoke($callable, $arguments);
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
    // TODO : vérifier l'utilité de renvoyer le previous instance !!!
    // TODO : utilité de cette méthode si on utilise le constructeur pour définir si l'instance de cette classe doit être accessible via la variable public self::$instance.
    public function setAsGlobal(): ?self
    {
        $previous = static::$instance;
        static::$instance = $this;

        return $previous;
    }

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    /*
    public function bindIf($abstract, $concrete = null, $shared = false)
    {
        if (! $this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }*/

    /**
     * Register a shared binding if it hasn't already been registered.
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    /*
    public function singletonIf($abstract, $concrete = null)
    {
        if (! $this->bound($abstract)) {
            $this->singleton($abstract, $concrete);
        }
    }*/


    /**
     * Wrap the given closure such that its dependencies will be injected when executed.
     *
     * @param  \Closure  $callback
     * @param  array  $parameters
     * @return \Closure
     */
    /*
    public function wrap(Closure $callback, array $parameters = [])
    {
        return function () use ($callback, $parameters) {
            return $this->call($callback, $parameters);
        };
    }*/


    /**
     * Get a closure to resolve the given type from the container.
     *
     * @param  string  $abstract
     * @return \Closure
     */
    /*
    public function factory($abstract)
    {
        return function () use ($abstract) {
            return $this->make($abstract);
        };
    }*/

    /**
     * Get the container's bindings.
     *
     * @return array
     */
    /*
    public function getBindings()
    {
        return $this->bindings;
    }*/

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    /*
    public function flush()
    {
        $this->aliases = [];
        $this->resolved = [];
        $this->bindings = [];
        $this->instances = [];
        $this->abstractAliases = [];
    }*/

}
