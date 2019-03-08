<?php

declare(strict_types=1);

namespace Chiron\Tests\Container\Methods;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Closure;
use Chiron\Container\Container;
use Chiron\Container\Exception\CannotResolveException;
use Chiron\Container\Reflection\ReflectionCallable;

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
