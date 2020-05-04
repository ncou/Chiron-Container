<?php

declare(strict_types=1);

namespace Chiron\Container\Definition;

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
class Reference //implements DefinitionInterface
{
    private $id;

    // private constructor you need to use the ::to() function to instanciate this class
    private function __construct($id)
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

    // TODO : il faudrait lever une exception du style EntryNotFound dans le cas ou la référence de l'alias n'existe pas.
    public function resolve(ContainerInterface $container, array $params = [])
    {
        if (empty($params)) {
            $result = $container->get($this->id);
        } else {
            $result = $container->get($this->id, $params);
        }

        return $result;
    }
}
