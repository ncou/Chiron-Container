<?php

namespace Wandu\DI\Descriptor;

use Chiron\Container\Container;
use PHPUnit\Framework\TestCase;
use stdClass;

class FreezeTest extends TestCase
{
    /**
     * @expectedException Chiron\Container\Exception\CannotChangeException
     * @expectedExceptionMessage it cannot be changed; "obj".
     */
    public function testFreeze()
    {
        $container = new Container();

        $container->instance('obj', new stdClass())->freeze();
        $container->destroy('obj');
    }
}
