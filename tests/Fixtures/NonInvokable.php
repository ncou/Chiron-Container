<?php

namespace Chiron\Tests\Container\Fixtures;

class NonInvokable
{
    public function __call($a, $b)
    {
    }
}
