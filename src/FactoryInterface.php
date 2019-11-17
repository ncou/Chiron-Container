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
    public function build(string $className, array $arguments = []);
}
