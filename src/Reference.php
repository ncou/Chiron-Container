<?php

declare(strict_types=1);

namespace Chiron\Container;

use Psr\Container\ContainerInterface;

/**
 * Class Reference allows us to define a dependency to a service in the container in another service definition.
 * For example:
 * ```php
 * [
 *    InterfaceA::class => ConcreteA::class,
 *    'alternativeForA' => ConcreteB::class,
 *    Service1::class => [
 *        '__construct()' => [
 *            Reference::to('alternativeForA')
 *        ]
 *    ]
 * ]
 * ```
 */

// TODO : Il faudrait pouvoir gérer la possibilité de faire un $forceNew !!!!
class Reference //implements DefinitionInterface
{
    private string $id;

    // private constructor you need to use the ::to() function to instanciate this class
    private function __construct(string $id)
    {
        $this->id = $id;
    }

/*
    public function getId(): string
    {
        return $this->id;
    }
*/

    public static function to(string $id): Reference
    {
        return new self($id);
    }

    // TODO : il faudrait lever une exception du style EntryNotFound dans le cas ou la référence de l'alias n'existe pas (c'est à dire que isBound === false, attention si on utilise has() et que l'alias est fait sur le nom d'une classe qui existe mais pas bindée dans le container, on lévera pas l'exception mais ce n'est pas le comportement qu'on souhaite.).
    // TODO : on devrait plutot lui passer un object Container::class plutot qu'une interface générique ContainerInterface::class
    // TODO : eventuellement remonter le code de la résolution directement dans la classe Definition, ca éviterai de porter une méthode resolve() dans cette classe.
    // TODO : renommer le paramétre $new en $forceNew, il faudra aussi changer les interfaces qui définissent la signature de la méthode resolve !!!!
    public function resolve(ContainerInterface $container, bool $new): mixed
    {
        // TODO : on devrait pas lever une exception si la clés de l'item à rechercher n'est pas bindée dans le container ? car ca limiterai les "références" à uniquement ce qui est déjà bound()===true, car c'est utilisé pour des alias, sans cette vérification on aurait une possiblité que l'utilisateur fasse une référence sur une classe non bindée et donc ca va créer la classe, ce qui n'est pas le but de cette classe !!!!

        return $container->get($this->id, $new);
    }
}
