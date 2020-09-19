<?php

declare(strict_types=1);

namespace Chiron\Container;

interface FactoryInterface
{
    /*
     * @param string $className
     * @param array  $arguments
     *
     * @return object
     */
    // TODO : ajouter le typehint pour le retour de la fonction avec "make(): object"
    public function make(string $className, array $arguments = []);
}
