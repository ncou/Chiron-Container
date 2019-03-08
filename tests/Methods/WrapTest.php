<?php

declare(strict_types=1);

namespace Chiron\Tests\Container\Methods;

use Chiron\Container\Container;
use Closure;
use PHPUnit\Framework\TestCase;
use stdClass;

class WrapTest extends TestCase
{
    public function testWrapFunction()
    {
        $container = new Container();

        /*
         * Wrap a function...
         */
        $result = $container->wrap(function (stdClass $foo, $bar = []) {
            return func_get_args();
        }, ['bar' => 'taylor']);

        $this->assertInstanceOf(Closure::class, $result);

        $result = $result();

        $this->assertInstanceOf(stdClass::class, $result[0]);
        $this->assertEquals('taylor', $result[1]);
    }
}
