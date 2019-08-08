<?php

declare(strict_types=1);

namespace Chiron\Container;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface FactoryInterface extends PsrContainerInterface
{
    /*
     * @param string $className
     * @param array  $arguments
     *
     * @return object
     */
    public function build(string $className, array $arguments = []);
}
