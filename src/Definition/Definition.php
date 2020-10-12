<?php

declare(strict_types=1);

namespace Chiron\Container\Definition;

use Chiron\Container\Container;
use Chiron\Container\Reference;

//https://github.com/thephpleague/container/blob/master/src/Definition/Definition.php
//https://github.com/symfony/dependency-injection/blob/master/Definition.php
//https://github.com/slince/di/blob/master/Definition.php

// TODO : ajouter le notion de Tags et une méthode "tagged" dans le container pour récupérer les éléments taggués : https://github.com/illuminate/container/blob/master/Container.php#L455

// TODO : il faudrait pas faire porter les mutations directement dans la classe Definition ? et elles seraient appliquées lors de l'appel de la méthode resolve. => attention pas forcément car tu peux ajouter une mutation sur une Interface, et lorsque tu vas résoudre une classe, tu n'auras pas les autres définitons de visible pour les interfaces implémentées par cette classe target. Ou alors rendre la méthode $container->mutate() public et la faire appeller depuis le Definition->resolve().

// TODO : renommer la classe en "Binding" ou "Bind" ????
final class Definition
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var mixed
     */
    private $concrete;

    /**
     * @var mixed
     */
    protected $resolved;

    /**
     * @var bool
     */
    private $shared = false;

    /**
     * @var array
     */
    // TODO : à virer utiliser plutot une classe Autowire dans le cas d'une classe avec des arguments/parameters pour le constructeur !!!
    private $arguments = [];

    /**
     * Constructor.
     *
     * @param string $name
     * @param mixed  $concrete
     */
    // TODO : utiliser $name au lieu de $id ????
    public function __construct(string $id, $concrete = null)
    {
        $concrete = $concrete ?? $id;
        $this->id = $id;
        $this->concrete = $concrete;
    }

    // TODO : attention c'est dangereux de laisser cette méthode accessible car l'utilisateur pourrait faire n'importe quoi, et il faudra surement faire un reset du $this->resolved en cas de changement de nom.
    // TODO : renommer en setName ????
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    // TODO : remplacer le nommage "shared" par "singleton" ou "asSingleton()" ????
    // TODO : il faudrait pas vider la variable $this->resolved si on change la valeur du setShared ??? pour éviter de conserver une ancienne valeur par exemple ??? non ???
    // TODO : utilité de pouvoir changer à la volée le caractére "singleton" ou non du binding ??? il faudrait pas plutot faire un remove du binding et le refaire ??? dans ce cas on passerai au constructeur, le nom / concréte et la valeur singleton (true/false) et cette classe de binding serait plutot une classe de "lecture seule" qui affiche ces valeurs mais qu'on ne peut pas modifier !!!!
    public function setShared(bool $shared = true): self
    {
        // TODO : on devrait pas lever une exception si on essaye de mettre une un concrete de type instance ou de type closure ou scalar en mode shared, ca n'a pas de sens !!! une instance sera toujour shared
        $this->shared = $shared;

        return $this;
    }

    // TODO : remplacer le nommage "shared" par "singleton" ????
    public function isShared(): bool
    {
        return $this->shared;
    }

    /**
     * {@inheritdoc}
     */
    public function getConcrete()
    {
        return $this->concrete;
    }

    /**
     * {@inheritdoc}
     */
    // TODO : à virer c'est dangereux de conserver cette méthode !!! ou alors il faut vider (cad mettre à null) le $this->resolved
    /*
    public function setConcrete($concrete): self
    {
        $this->concrete = $concrete;

        return $this;
    }*/

    /**
     * {@inheritdoc}
     */
    public function addArgument($arg): self
    {
        $this->arguments[] = $arg;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addArguments(array $args): self
    {
        foreach ($args as $arg) {
            $this->addArgument($arg);
        }

        return $this;
    }

    // TODO ; utilité de laisser cette méthode en "public" ??? on devrait plutot la mettre en private !!!
    public function isResolved(): bool
    {
        return $this->resolved !== null;
    }

    // TODO : déplacer ici la logique du $new pour stocker dans cette classe le résultat de la résolution dans $this->resolved     https://github.com/thephpleague/container/blob/master/src/Definition/Definition.php#L193

    // TODO : on devrait pas virer cette méthode car elle n'a rien à faire en public ??? et il faudrait plutot que cette méthode soit déplacée dans la classe Container cela éviterai de lui passer en attribut un $container !!! => Cela me semble plus logique que cette fonction "métier" soit plutot au niveau de la classe Container (ca fait pas de sens d'avoir une classe Definition en mode stand alone qu'on résoudrait par la suite en lui passant un container, ca ne serai pas logique surtout si on doit un jour faire porter les attributs "TAG" dans cette classe de définition !!!).
    // return mixed
    // TODO : renomer le parmatré $new en $forceNew
    public function resolve(Container $container, bool $new)
    {
        // handle the singleton case.
        // TODO : utiliser la méthode $tis->isResolved()
        if ($this->isShared() && $this->resolved !== null && $new === false) {
            return $this->resolved;
        }

        $concrete = $this->concrete;
        $this->resolved = $concrete;

        // TODO : permettre de passer dans le concrete un tableau avec un nom string de class ou une instance de classe et un second paramétre de type string qui est une méthode privée. Utiliser la reflection ->setAccessible(true) pour permettre d'invoker cette méthode. Cela est utilse lors de la création de "factory" de type ->bind('id', ['class', 'method']) et que la méthode est privée.  ====>  https://github.com/spiral/core/blob/master/src/Container.php#L499

        // TODO : comment ca sez passe si on a mis dans la définition une instance d'une classe qui a une méthode __invoke ???? elle va surement être interprété comme un callable mais ce n'est pas ce qu'on souhaite !!!!
        // TODO : il faudrait ajouter aussi une vérif soit "différent de object", sinon ajouter un if en début de proécédure dans le cas ou c'est un "scalaire ou objet" on n'essaye pas de résoudre la variable $concrete.
        // TODO : il faudra surement résoudre les arguments, car par exemple on pourrait avoir comme argument une classe Reference pour les arguments du constructeur. ex : bind(Foobar::class)->addArgument(['request' => Reference::to('XXXXX')]) ou une classe Raw par exemple.
        if (is_callable($concrete)) {
            // TODO : attention il faudra gérer le cas ou les arguments sont de type "Reference" par exemple il faudra les résoudre avant de faire l'appel à la méthode invoke !!!
            $this->resolved = $container->invoke($concrete, $this->arguments);
        }

        if (is_string($concrete) && class_exists($concrete)) {
            // TODO : attention il faudra gérer le cas ou les arguments sont de type "Reference" par exemple il faudra les résoudre avant de faire l'appel à la méthode build !!!
            $this->resolved = $container->build($concrete, $this->arguments);
        }

        // TODO : créer une interface ResolvableInterface pour la signature de la méthode "resolve()" ? ca permettrait de l'utiliser pour les classes Raw/Reference et d'externaliser cette méthode de la classe Definition.
        if ($concrete instanceof Reference) {
            // TODO : éventuellement pour éviter de porter la logique dans la méthode Reference::resolve, on pourrait faire le $container->get() directement ici, et ne laisser qu'une méthode dans cette classe "getValue" par exemple. Ca permettrait de préparer la prochaine classe "Raw" qui retournerai directement le résultat "$resolved = $concrete->getValue()"

            // TODO : on devrait pas lever une exception si la clés de l'item à rechercher n'est pas bindée dans le container ? car ca limiterai les "références" à uniquement ce qui est déjà bound()===true, car c'est utilisé pour des alias, sans cette vérification on aurait une possiblité que l'utilisateur fasse une référence sur une classe non bindée et donc ca va créer la classe, ce qui n'est pas le but de cette classe !!!!

            $this->resolved = $concrete->resolve($container, $new);
        }

        // Let's start the mutations !
        $this->resolved = $container->mutate($this->resolved);

        return $this->resolved;
    }
}
