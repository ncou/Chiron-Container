<?php

declare(strict_types=1);

namespace Chiron\Container;

use Chiron\Container\Definition\Definition;
use Chiron\Container\Exception\ContainerException;
use Chiron\Container\Exception\CircularDependencyException;
use Chiron\Container\Exception\EntryNotFoundException;
use Chiron\Container\Exception\BindingResolutionException;
use Chiron\Container\Mutation\Mutation;
use Chiron\Container\Mutation\MutationInterface;
use Chiron\Injector\Injector;
use Chiron\Injector\InvokerInterface;
use Chiron\Injector\FactoryInterface;
use LogicException;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;
use Chiron\Injector\Exception\InjectorException;

//https://github.com/illuminate/container/blob/0953cf5ed6985839b3061548a15deabe9ce2e72d/Container.php

//https://github.com/laravel/framework/blob/8.x/src/Illuminate/Container/Container.php
//https://github.com/laravel/framework/blob/8.x/src/Illuminate/Container/BoundMethod.php

// TODO : améliorer le setAsGlobal    https://github.com/illuminate/support/blob/7890f06161367e8337462e4a439671e387c8fd7e/Traits/CapsuleManagerTrait.php#L44   +     https://github.com/illuminate/database/blob/master/Capsule/Manager.php#L198

// TODO : wrapCallback =>   https://github.com/flarum/core/blob/master/src/Foundation/ContainerUtil.php

// TODO : vérification si la classe passée en paramétre implémente bien l'interface attendue (cela peut servir pour la "mutation") mais aussi pour s'assurer qu'on bind correctement l'interface avec la classe qui est donnée dans le paramétre concréte.
//https://github.com/ray-di/Ray.Di/blob/2.x/src/di/BindValidator.php#L57
//https://github.com/ray-di/Ray.Di/blob/2.x/src/di/BindValidator.php#L35
// TODO : utiliser une classe pour définir le scope singleton =>    https://github.com/ray-di/Ray.Di/blob/2.x/src/di/Scope.php#L12

// TODO : vérification qu'on ne bind pas une interface à elle même =>    https://github.com/andy-shea/pulp/blob/master/lib/Binding/Binding.php#L38


// TODO : container inspiré par google guice qui a une notation de méthode interessante : exemple     bind(xxx)->toInstance(xxx)
//https://github.com/ray-di/Ray.Di
//https://github.com/researchgate/injektor/blob/master/src/rg/injektor/

//https://github.com/symfony/symfony/blob/4dd6e2f0b2daefc2bddd08aa056370afb1c1cb1d/src/Symfony/Component/DependencyInjection/Container.php#L236
//https://github.com/symfony/symfony/blob/master/src/Symfony/Component/DependencyInjection/Container.php#L309

//https://github.com/illuminate/container/blob/master/Container.php

// TODO : créer une méthode singleton() ou share() => https://github.com/illuminate/container/blob/master/Container.php#L354
// https://github.com/thephpleague/container/blob/master/src/Container.php#L92
//https://github.com/mrferos/di/blob/master/src/Container.php#L99


//https://github.com/spiral/core/blob/master/src/Container.php
//https://github.com/thephpleague/container/blob/master/src/Container.php
//https://github.com/yiisoft/di/blob/master/src/Container.php


// TODO : améliorer le Circular exception avec le code :
//https://github.com/symfony/dependency-injection/blob/master/Container.php#L236
//https://github.com/symfony/symfony/blob/5.4/src/Symfony/Component/DependencyInjection/Container.php#L229
//https://github.com/PHP-DI/PHP-DI/blob/master/src/Container.php#L380

// TODO : ajouter une méthode "removeBinding($serviceName)" et la rajouter dans la classe BindingInterface  => https://github.com/spiral/core/blob/master/src/Container.php#L354

/**
 * This class implements a [dependency injection] container pattern.
 *
 * @see http://en.wikipedia.org/wiki/Dependency_injection
 */
final class Container implements ContainerInterface, BindingInterface
{
    /** @var static */
    // TODO : faire une méthode getInstance (qui retournerai l'instance du container ou null si on n'a pas appellé la méthode setAsGlobal) ???? ca éviterai d'avoir une propriété public dans la classe. On pourrait même lever une exception si le container n'est pas initialisé et qu'on fait l'appel à la méthode getInstance !!!!
    // TODO : initialiser cette variable static à "null" ???
    public static $instance;

    /** @var Injector */
    private $injector;

    /** @var \Chiron\Container\Definition\Definition[] */
    // TODO : on devrait pas renommer ce tableau en "bindings" ?
    private array $definitions = [];

    /** @var \Chiron\Container\Mutation[] */
    private array $mutations = [];

    /** @var array<bool> */
    private array $entriesBeingResolved = []; // TODO : utiliser plutot un SplObjectStorage et stocker l'objet Definition::class pour utiliser les méthodes contains/attach/detach et si besoin iterator_to_array    https://github.com/nette/di/blob/f3608c4d8684c880c2af0cf7b4d2b7143bc459b0/src/DI/Resolver.php#L46

    /** @var array<mixed> */
    private array $entriesResolved = [];

    /** @var bool */
    private bool $defaultToShared = false;

    /**
     * Container constructor.
     */
    public function __construct(bool $global = true)
    {
        // TODO : ajouter un PHPunit pour vérifier si ces 4 classes sont bien bindées à la construction de la classe !!!
        $this->singleton(Container::class, $this);
        $this->singleton(ContainerInterface::class, $this); // TODO : utiliser plutot ->alias() ????
        $this->singleton(BindingInterface::class, $this); // TODO : utiliser plutot ->alias() ????

        $this->injector = new Injector($this);
        $this->singleton(Injector::class, $this->injector);
        $this->singleton(FactoryInterface::class, $this->injector); // TODO : utiliser plutot ->alias() ????
        $this->singleton(InvokerInterface::class, $this->injector); // TODO : utiliser plutot ->alias() ????

        if ($global) {
            $this->setAsGlobal();
        }
    }

    public function injector(): Injector
    {
        return $this->injector;
    }

    /**
     * Allows for manipulation of specific types on resolution.
     *
     * @param string   $type     represent the class name
     * @param callable $callback
     *
     * @return MutationInterface
     */
    // TODO : renommer en mutation ou en interceptor ? avec une méthode qui s'execute soit respectivement "mutate()" ou "proceed()"
    // TODO : vérifier l'utilité d'avoir une classe MutationInterface ??? on pourrait directement utiliser la classe Mutation::class
    public function mutation(string $type, callable $callback): MutationInterface
    {
        return $this->mutations[] = new Mutation($type, $callback);
    }

    /**
     * Add an alias for an existing binding.
     *
     * @param string $alias
     * @param string $target
     *
     * @return Definition
     */
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
     * @param bool|null $shared
     *
     * @return Definition
     */
    // TODO : ajouter une vérif (et lever une erreur) sur le binding d'un service qui est déjà initialisé : https://github.com/symfony/symfony/blob/4dd6e2f0b2daefc2bddd08aa056370afb1c1cb1d/src/Symfony/Component/DependencyInjection/Container.php#L172           +                   https://github.com/symfony/symfony/blob/4dd6e2f0b2daefc2bddd08aa056370afb1c1cb1d/src/Symfony/Component/DependencyInjection/Container.php#L293
    // TODO : lever une erreur si on essaye de rebinder un service qui est de type "Privé" ou éventuellement utiliser plutot un attribut "freezed" dans la classe Definition pour indiquer qu'on ne peut pas redéfinir(cad rebinder) ce service !!!!
    // TODO : vérifier le type de concréte qu'on veut binder. exemple : https://github.com/yiisoft/di/blob/master/src/Container.php#L168
    // TODO : vérifier si il n'y a pas déjà un binding avec ce nom, plus le case sensitive !!!! https://github.com/nette/di/blob/aa7ce9cc8693da45c60cf9b8120e94e43e7c5d34/src/DI/ContainerBuilder.php#L63
    public function bind(string $name, $concrete = null, ?bool $shared = null): Definition
    {
        // TODO : lever une exception si le $concrete n'est pas du bon type : https://github.com/illuminate/container/blob/master/Container.php#L263
        // handle special case when the $name is the interface name and the $concrete the real class.
        // TODO : bout de code à virer si on vérifie que les string qui ne sont pas des noms de classes lévent une erreur.
        if (is_string($concrete) && class_exists($concrete)) {
            // Attention il faut améliorer ce bout de code car en passant par la méthode 'alias()' cela force la valeur du shared à false, ce qui n'est peut etre pas le comportement attentu si l'utilisateur a mis une valeur au paramétre $shared lorsqu'il a appellé cette méthode bind() !!!!!
            // Attention ce bout de code fonctionne car la classe utilisée pour l'alias "Reference::class" fait un get qui force l'autowire si on change ce comportement (car ce n'est pas logique pour la classe référence d'instancier une autre classe) cela va casser le code de la méthode bind. Il faudrait plutot créer une classe Autowire::class pour gérer ce cas là !!! Voir même appeller directement la méthode "$this->autowire()", par contre il faudra ajouter un paramétre shared = true/false à cette méthode !!!!
            $this->alias($concrete, $name);
        }

        $concrete = $concrete ?? $name;
        $shared = $shared ?? $this->defaultToShared;

        // TODO : virer ce cas là !!! on ne doit pas pouvoir passer d'objet Definition dans le paramétre $concrete !!!! Comme ca on pourra virer la méthode setName qui est dangereuse !!!!
        if (! $concrete instanceof Definition) {
            $concrete = new Definition($name, $concrete);
        }

        $this->definitions[$name] = $concrete
            ->setName($name)
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
    // TODO : ajouter dans le phpdoc que cette méthode throw un EntryNotFoundException !!!!
    public function get($name, bool $new = false)
    {
        if (! $this->bound($name)) {
            //No direct instructions how to construct class, bind a new definition.
            $this->autowire($name);
        }

        return $this->resolve($name, $new);
    }

    // TODO : appeller la méthode make() dans ce cas là ????
    private function autowire(string $name): void
    {
        //assert(class_exists($class->name));

        // TODO : il faudrait faire un test d'enlever cette vérification que la classe existe, car même si on bind une classe qui n'existe pas il doit bien il y avoir un test plus tard pour vérifier ce cas là !!!! Ou alors cela va retourner une simple chaine de caractéres ???? faire le test de ce cas là ca sera plus simple...
        if (! class_exists($name)) {
            // TODO : utiliser la fonction levenshtein pour afficher les services le plus proche du nom du service recherché ?     https://github.com/symfony/dependency-injection/blob/b4f099e65175874bd326ec9a86d6df57a217a6a4/Container.php#L268
            // TODO : utiliser aussi le $this->entriesBeingResolved pour afficher plus d'infos notamment la classe qui est à l'origine de la résolution. Exemple :    https://github.com/symfony/symfony/blob/5.4/src/Symfony/Contracts/Service/ServiceLocatorTrait.php#L113
            // TODO : utiliser un levenshtein pour afficher le nom du service le plus proche. Par exemple :
            //https://github.com/symfony/dependency-injection/blob/6b12d5bcd1e2f1cc9507cea6181787d642ec46b5/Container.php#L265
            //https://github.com/symfony/dependency-injection/blob/6b12d5bcd1e2f1cc9507cea6181787d642ec46b5/ServiceLocator.php#L84
            throw new EntryNotFoundException($name); // \sprintf("Undefined class or binding '%s'", $class) ou alors : 'You have requested a non-existent service "%s".'
        }

        // TODO : faire plutot un if/else sur $class is_a($class, SingletonInterface::class, true) et si c'est vrai on appel $this->singleton(), else $this->bind(), le code sera plus lisible de cette maniére !!!
        // if the class to build in an instanceof SingletonInterface, we force the share parameter at true.
        $share = is_subclass_of($name, SingletonInterface::class) ? true : null;
        $this->bind($name, null, $share);
    }

    /**
     * Resolve the definition and apply mutations if needed.
     *
     * @param string $name
     * @param bool   $new
     *
     * @return mixed The resolved and mutated object
     */
    // TODO : renommer le paramétre $new en $forceNew
    // TODO : renommer le paramétre $name en $entry ????
    private function resolve(string $name, bool $new)
    {
        //$definition = $this->definitions[$name];
        // TODO : ce bout de code ne devrait plus servir car quand on va virer la méthode setId() de la classe définition, la variable $name sera toujours la même que la valeur du getId() donc on pourra virer $entry et utiliser à la place $name !!!!
        //$entry = $definition->getName(); // TODO : question béte : le $name est différent du $definition->getName() ????

        // TODO : améliorer la gestion des exceptions circulaires en affichant dans l'exception l'ensemble des classes initialisée précédemment comme ca on retrouvera l'origine de l'appel (cad la 1ere classe qu'on essaye de résoudre via le get !!!!)
        // Check if we are already getting this entry -> circular dependency
        if (isset($this->entriesBeingResolved[$name])) {
            // TODO : créer une DependencyException ???? ou une CircularReferenceException ou une CircularDependencyException (https://github.com/laravel/framework/blob/277c2fbd0cebd2cb194807654d870f4040e288c0/src/Illuminate/Contracts/Container/CircularDependencyException.php)
            // https://github.com/symfony/dependency-injection/blob/6b12d5bcd1e2f1cc9507cea6181787d642ec46b5/Container.php#L230
            // https://github.com/symfony/dependency-injection/blob/94d973cb742d8c5c5dcf9534220e6b73b09af1d4/Exception/ServiceCircularReferenceException.php
            /*
            throw new ContainerException(sprintf(
                'Circular dependency detected while trying to resolve entry "%s"',
                $name
            ));*/

            // TODO : améliorer le code versus le array_merge !!!!   https://github.com/nette/di/blob/16f7d617d8ec5a08b0c4700f4cfc488fde4ed457/src/DI/Resolver.php#L58
            throw new CircularDependencyException($name, array_merge(array_keys($this->entriesBeingResolved), [$name]));

        }
        $this->entriesBeingResolved[$name] = true;

        // TODO : dans try attraper les exceptions du genre EntryNotFoundException / ClassNotFoundException / ClassNotInstantiableException et faire un throw d'un BindingResolutionException en utilisant le $this->entriesBeingResolved pour afficher toute la chaine de résolution qui a emmené cette erreur.
        try {
            // resolve and apply mutations.
            //$resolved = $definition->resolve($this, $new);
            $resolved = $this->resolveDefinition($this->definitions[$name], $new);
        } catch (EntryNotFoundException $e) {
            // TODO : ce cas n'existera plus suite à la modification du composant Injector !!!! Donc ce catch est à virer !!!!
            $message = sprintf('The service "%s" has a dependency on a non-existent service "%s".', $name, $e->getEntry());
            throw new BindingResolutionException($message, $e->getCode(), $e);
        } catch (InjectorException $e) {
            $message = sprintf('The service "%s" cannot be resolved: %s.', $name, rtrim(lcfirst($e->getMessage()),'.')); // TODO : virer le trim sur le point si on s'assure que toutes les exception de type InjectorException on bien un message qui se termine par un point !!!
            throw new BindingResolutionException($message, $e->getCode(), $e);
        } finally {
            unset($this->entriesBeingResolved[$name]);
        }

        return $resolved;
    }

    // TODO : renomer le paramétre $new en $forceNew
    private function resolveDefinition(Definition $definition, bool $new)
    {
        $entry = $definition->getName();
        $resolved = $this->entriesResolved[$entry] ?? null;

        // handle the singleton case.
        if ($definition->isShared() && $resolved !== null && $new === false) {
            return $resolved;
        }

        $concrete = $definition->getConcrete();
        $resolved = $concrete;

        // TODO : permettre de passer dans le concrete un tableau avec un nom string de class ou une instance de classe et un second paramétre de type string qui est une méthode privée. Utiliser la reflection ->setAccessible(true) pour permettre d'invoker cette méthode. Cela est utilse lors de la création de "factory" de type ->bind('id', ['class', 'method']) et que la méthode est privée.  ====>  https://github.com/spiral/core/blob/master/src/Container.php#L499

        // TODO : comment ca se passe si on a mis dans la définition une instance d'une classe qui a une méthode __invoke ???? elle va surement être interprété comme un callable mais ce n'est pas ce qu'on souhaite !!!!
        // TODO : il faudrait ajouter aussi une vérif soit "différent de object", sinon ajouter un if en début de proécédure dans le cas ou c'est un "scalaire ou objet" on n'essaye pas de résoudre la variable $concrete.
        // TODO : il faudra surement résoudre les arguments, car par exemple on pourrait avoir comme argument une classe Reference pour les arguments du constructeur. ex : bind(Foobar::class)->addArgument(['request' => Reference::to('XXXXX')]) ou une classe Raw par exemple.
        if (is_callable($concrete)) {
            // TODO : attention il faudra gérer le cas ou les arguments sont de type "Reference" par exemple il faudra les résoudre avant de faire l'appel à la méthode invoke !!!
            $resolved = $this->injector->invoke($concrete, $definition->getArguments());
        }

        if (is_string($concrete) && class_exists($concrete)) {
            // TODO : attention il faudra gérer le cas ou les arguments sont de type "Reference" par exemple il faudra les résoudre avant de faire l'appel à la méthode build !!!
            $resolved = $this->injector->build($concrete, $definition->getArguments());
        }

        // TODO : créer une interface ResolvableInterface pour la signature de la méthode "resolve()" ? ca permettrait de l'utiliser pour les classes Raw/Reference et d'externaliser cette méthode de la classe Definition.
        if ($concrete instanceof Reference) {
            // TODO : éventuellement pour éviter de porter la logique dans la méthode Reference::resolve, on pourrait faire le $container->get() directement ici, et ne laisser qu'une méthode dans cette classe "getValue" par exemple. Ca permettrait de préparer la prochaine classe "Raw" qui retournerai directement le résultat "$resolved = $concrete->getValue()"

            // TODO : on devrait pas lever une exception si la clés de l'item à rechercher n'est pas bindée dans le container ? car ca limiterai les "références" à uniquement ce qui est déjà bound()===true, car c'est utilisé pour des alias, sans cette vérification on aurait une possiblité que l'utilisateur fasse une référence sur une classe non bindée et donc ca va créer la classe, ce qui n'est pas le but de cette classe !!!!

            $resolved = $concrete->resolve($this, $new);
        }

        // Let's start the mutations !
        $resolved = $this->mutate($resolved); // TODO : utiliser plutot le event-dispatcher pour simuler une mutation en faisant un listener sur l'évenement ResolvedEntryEvent ou DefinitionResolvedEvent($definition, $resolved, $force). Ou alors éventuellement ne passer que le résultat dans cet évenement !!!

        return $this->entriesResolved[$entry] = $resolved;
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
    // TODO : déplacer cette méthode dans la classe Definition pour améliorer le resolve() et surtout faire une méthode public Container::getMutations() qui servira à récupérer $this->mitations[]
    // TODO : virer cette partie là et utiliser plutot un eventDispatcher pour l'événement AfterResolvingEvent qui se chargera de faire la mutation !!!!
    private function mutate($target)
    {
        foreach ($this->mutations as $mutation) {
            $type = $mutation->getType();

            if (! $target instanceof $type) {
                continue;
            }

            // TODO : il faudrait pas que l'on stocke le retour de cette appel dans $target, pour gérer le cas des objets immuables. Et il faudrait dans ce cas revérifier que le type de retour est bien toujours le même type d'object qu'on a recu en entrée.
            call_user_func($mutation->getCallback(), $target);
        }

        // TODO : il faudrait pas faire une vérification que l'instance mutée est bien du même type d'objet que celui qu'on a eu en entrée de la fonction ? cela évitera que la mutation retourne un autre type d'objet, ce n'est pas le but de la mutation !!!!
        return $target;
    }

    /**
     * {@inheritdoc}
     */
    // TODO : renommer les paramétres $name en $entry ???? <== attention utiliser le même de paramétre que celui défini dans l'interface PSR11 !!!!
    public function has($name): bool
    {
        return $this->bound($name) || class_exists($name);
    }

    /**
     * Determine if the given entry has been bound.
     *
     * @param string $name
     *
     * @return bool
     */
    public function bound(string $name): bool
    {
        return isset($this->definitions[$name]);
    }

    // TODO : utilité de la méthode ????
    // TODO : renommer en removeBinding() ???? ou plutot en remove() et ca se chargerai de supprimer un binding, un singleton ou un alias car c'est tout dans le même tableau "$this->definitions"
    // TODO : retourner un booléen si le unbinding s'est bien passé ? ou alors rester sur un void ?
    // TODO : on devrait pas faire un test "isbound === true" avant de faire le unset ? et lever une exception si on essaye d'enlever un service qui n'est pas bindé !!!
    // TODO : renommer la méthode en unbound()
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
    // TODO : ajouter le typehint pour le retour de la fonction avec "make(): object"
    /*
    public function make(string $className, array $arguments = [])
    {
        // TODO : il faudrait pas convertir les exceptions remontées par cette fonction "build" en ContainerException ???? faire un try/catch et change le type de l'exception ????
        // TODO : il faudrait vérifier que le $className est bien une classe qui existe !!!! is_class()
        // TODO : il faudra appliquer les mutations enregistrées pour cette classe une fois qu'on a fait le build. $this->mutate($instance);
        // TODO : il faudra surement améliorer le message des références circulaires, car on il faudra indiquer que le point d'entrée est la tentative de résolution de $className
        return $this->injector->build($className, $arguments);
    }*/

    /*
    public function build(string $className, array $arguments = [])
    {
        return $this->make($className, $arguments);
    }*/

    // return mixed
    // TODO : ne surtout PAS forcer le typehint à callable car il serait possible de faire un CallableResolver avant, non ????
    // $callable can be a : callable|array|string
    /*
    public function call($callable, array $arguments = [])
    {
        // TODO : il faudra surement améliorer le message des références circulaires, car on il faudra indiquer que le point d'entrée est la tentative de résolution de $callable
        // TODO : il faudrait pas convertir les exceptions remontées par cette fonction "build" en ContainerException ???? faire un try/catch et change le type de l'exception ????
        return $this->injector->invoke($callable, $arguments);
    }*/

    // TODO : méthode à virer !!!
    /*
    public function invoke($callable, array $arguments = [])
    {
        // TODO : il faudra surement améliorer le message des références circulaires, car on il faudra indiquer que le point d'entrée est la tentative de résolution de $callable
        // TODO : il faudrait pas convertir les exceptions remontées par cette fonction "build" en ContainerException ???? faire un try/catch et change le type de l'exception ????
        return $this->call($callable, $arguments);
    }*/

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
     * Initialise the instance with the $this value, and return the previous instance (or null on the first call)
     *
     * @return static|null previous instance
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
     *
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
     *
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
     *
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
     *
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
