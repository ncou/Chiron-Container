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

class FactoryTest extends TestCase
{

    public function testFactoryFunction()
    {
        $container = new Container();

        $container->bind('name', function () {
            return 'Taylor';
        });

        $factory = $container->factory('name');

        $this->assertEquals($container->get('name'), $factory());

    }
}
