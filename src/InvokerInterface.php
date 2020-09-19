<?php

declare(strict_types=1);

namespace Chiron\Container;

interface InvokerInterface
{
    /*
     * @param callable|array|string $callable
     * @param array  $arguments
     *
     * @return mixed
     */
    public function call($callable, array $arguments = []);
}
