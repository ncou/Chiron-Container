<?php

declare(strict_types=1);

namespace Chiron\Container;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface InvokerInterface extends PsrContainerInterface
{
    /*
     * Call the given callable / string 'class@method' and inject its dependencies.
     *
     * @param callable|string $callback
     * @param array           $parameters
     * @param string|null     $defaultMethod
     *
     * @return mixed
     */
    public function call($callback, array $parameters = [], ?string $defaultMethod = null);
}
