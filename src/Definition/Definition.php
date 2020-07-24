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
    private $arguments = [];

    /**
     * Constructor.
     *
     * @param string $name
     * @param mixed  $concrete
     */
    public function __construct(string $id, $concrete = null)
    {
        $concrete = $concrete ?? $id;
        $this->id = $id;
        $this->concrete = $concrete;
    }

    /**
     * {@inheritdoc}
     */
    // TODO : attention c'est dangereux de laisser cette méthode accessible car l'utilisateur pourrait faire n'importe quoi, et il faudra surement faire un reset du $this->resolved en cas de changement de nom.
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setShared(bool $shared = true): self
    {
        // TODO : on devrait pas lever une exception si on essaye de mettre une un concrete de type instance ou de type closure ou scalar en mode shared, ca n'a pas de sens !!! une instance sera toujour shared
        $this->shared = $shared;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
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

    public function isResolved(): bool
    {
        return $this->resolved !== null;
    }

    // TODO : déplacer ici la logique du $new pour stocker dans cette classe le résultat de la résolution dans $this->resolved     https://github.com/thephpleague/container/blob/master/src/Definition/Definition.php#L193
    // return mixed
    public function resolve(Container $container, bool $new)
    {
        // handle the singleton case.
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
            $this->resolved = $container->invoke($concrete, $this->arguments);
        }

        if (is_string($concrete) && class_exists($concrete)) {
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
