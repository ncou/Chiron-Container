<?php

declare(strict_types=1);

namespace Chiron\Tests\Container;

use Chiron\Container\Container;
use PHPUnit\Framework\TestCase;

class CircularDependencyTest extends TestCase
{
    /**
     * @expectedException Chiron\Container\Exception\DependencyException
     * @expectedExceptionMessage Circular dependency detected while trying to resolve entry
     */
    public function testGetByCreateCircularDependency()
    {
        $container = new Container();

        $object = $container->get(Class1CircularDependency::class);
        static::assertInstanceOf(Class1CircularDependency::class, $object);
    }

    /**
     * @expectedException Chiron\Container\Exception\DependencyException
     * @expectedExceptionMessage Circular dependency detected while trying to resolve entry
     */
    public function testGetByCreateCircularDependencyFromContainer()
    {
        $container = new Container();

        $container->bind(Class1CircularDependency::class);
        $container->bind(Class2CircularDependency::class);

        $object = $container->get(Class1CircularDependency::class);

        static::assertInstanceOf(Class1CircularDependency::class, $object);
    }

    /**
     * @expectedException Chiron\Container\Exception\DependencyException
     * @expectedExceptionMessage Circular dependency detected while trying to resolve entry
     */
    public function testBuildToCreateCircularDependency()
    {
        $container = new Container();

        $object = $container->build(Class1CircularDependency::class);
        static::assertInstanceOf(Class1CircularDependency::class, $object);
    }

    /**
     * @expectedException Chiron\Container\Exception\DependencyException
     * @expectedExceptionMessage Circular dependency detected while trying to resolve entry
     */
    public function testBuildToCreateCircularDependencyFromContainer()
    {
        $container = new Container();

        $container->bind(Class1CircularDependency::class);
        $container->bind(Class2CircularDependency::class);

        $object = $container->build(Class1CircularDependency::class);

        static::assertInstanceOf(Class1CircularDependency::class, $object);
    }

    /**
     * @expectedException Chiron\Container\Exception\DependencyException
     * @expectedExceptionMessage Circular dependency detected while trying to resolve entry
     */
    public function testGetByCreateCircularDependencyFromClosure()
    {
        $container = new Container();

        $container->bind(Class1CircularDependency::class);
        $container->bind(Class2CircularDependency::class);

        $callable = function (Class1CircularDependency $class) {
            return $class;
        };

        $object = $container->call($callable);

        static::assertInstanceOf(Class1CircularDependency::class, $object);
    }

    /**
     * @expectedException Chiron\Container\Exception\DependencyException
     * @expectedExceptionMessage Circular dependency detected while trying to resolve entry
     */
    public function testGetByCreateCircularDependencyFromClosureInContainer()
    {
        $container = new Container();

        $container->bind(Class1CircularDependency::class);
        $container->bind(Class2CircularDependency::class);

        $callable = function (Class1CircularDependency $class) {
            return $class;
        };

        $container->closure('circular', $callable);

        $object = $container->get('circular');

        static::assertInstanceOf(Class1CircularDependency::class, $object);
    }
}

class Class1CircularDependency
{
    public function __construct(Class2CircularDependency $class2)
    {
        $this->class2 = $class2;
    }
}

class Class2CircularDependency
{
    public function __construct(Class1CircularDependency $class1)
    {
        $this->class1 = $class1;
    }
}
