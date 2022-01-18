<?php

declare(strict_types=1);

namespace Chiron\Container\Definition;

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
    private $name;

    /**
     * @var mixed
     */
    private $concrete;

    /**
     * @var mixed
     */
    private $resolved;

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
    public function __construct(string $name, $concrete = null)
    {
        $concrete = $concrete ?? $name;
        $this->name = $name;
        $this->concrete = $concrete;
    }

    // TODO : attention c'est dangereux de laisser cette méthode accessible car l'utilisateur pourrait faire n'importe quoi, et il faudra surement faire un reset du $this->resolved en cas de changement de nom.
    // TODO : renommer en setName ????
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
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

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
