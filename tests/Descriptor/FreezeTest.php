<?php
namespace Wandu\DI\Descriptor;

use PHPUnit\Framework\TestCase;
use stdClass;
use Chiron\Container\Container;
use Chiron\Container\Exception\CannotChangeException;

class FreezeTest extends TestCase
{

    /**
     * @expectedException Chiron\Container\Exception\CannotChangeException
     * @expectedExceptionMessage it cannot be changed; "obj".
     */
    public function testFreeze()
    {
        $container = new Container();

        $container->instance('obj', new stdClass)->freeze();
        $container->destroy('obj');
    }
}
