<?php

declare(strict_types=1);

namespace Chiron\Container;

interface InvokerInterface
{
    /*
     * @param string $className
     * @param array  $arguments
     *
     * @return object
     */
    // TODO : renommer la méthode en call()
    public function invoke(callable $callable, array $arguments = []);
}
