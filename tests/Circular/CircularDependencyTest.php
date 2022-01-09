<?php

declare(strict_types=1);

namespace Chiron\Tests\Container\Circular;

use Chiron\Container\Container;
use PHPUnit\Framework\TestCase;
use Chiron\Container\Exception\CircularDependencyException;

class CircularDependencyTest extends TestCase
{
    public function testGetByCreateCircularDependencyFromAlias()
    {
        $this->expectExceptionMessage('Circular dependency detected while trying to resolve entry');
        $this->expectException(CircularDependencyException::class);

        $container = new Container();

        $container->alias('AliasA', 'AliasA');

        $object = $container->get('AliasA');
    }

    public function testGetByCreateCircularDependencyFromAlias2()
    {
        $this->expectExceptionMessage('Circular dependency detected while trying to resolve entry');
        $this->expectException(CircularDependencyException::class);

        $container = new Container();

        $container->alias('AliasA', 'LinkA');
        $container->alias('LinkA', 'AliasA');

        $object = $container->get('AliasA');
    }

    public function testGetByCreateCircularDependency()
    {
        $this->expectExceptionMessage('Circular dependency detected while trying to resolve entry');
        $this->expectException(CircularDependencyException::class);

        $container = new Container();

        $object = $container->get(Class1CircularDependency::class);
        static::assertInstanceOf(Class1CircularDependency::class, $object);
    }

    public function testGetByCreateCircularDependencyFromContainer()
    {
        $this->expectExceptionMessage('Circular dependency detected while trying to resolve entry');
        $this->expectException(CircularDependencyException::class);

        $container = new Container();

        $container->bind(Class1CircularDependency::class);
        $container->bind(Class2CircularDependency::class);

        $object = $container->get(Class1CircularDependency::class);

        static::assertInstanceOf(Class1CircularDependency::class, $object);
    }

    public function testBuildToCreateCircularDependency()
    {
        $this->expectExceptionMessage('Circular dependency detected while trying to resolve entry');
        $this->expectException(CircularDependencyException::class);

        $container = new Container();

        $object = $container->build(Class1CircularDependency::class);
        static::assertInstanceOf(Class1CircularDependency::class, $object);
    }

    public function testBuildToCreateCircularDependencyFromContainer()
    {
        $this->expectExceptionMessage('Circular dependency detected while trying to resolve entry');
        $this->expectException(CircularDependencyException::class);

        $container = new Container();

        $container->bind(Class1CircularDependency::class);
        $container->bind(Class2CircularDependency::class);

        $object = $container->build(Class1CircularDependency::class);

        static::assertInstanceOf(Class1CircularDependency::class, $object);
    }

    /*
    public function testGetByCreateCircularDependencyFromClosure()
    {
        $this->expectExceptionMessage('Circular dependency detected while trying to resolve entry');
        $this->expectException(\Chiron\Container\Exception\ContainerException::class);

        $container = new Container();

        $container->bind(Class1CircularDependency::class);
        $container->bind(Class2CircularDependency::class);

        $callable = function (Class1CircularDependency $class) {
            return $class;
        };

        $object = $container->call($callable);

        static::assertInstanceOf(Class1CircularDependency::class, $object);
    }*/

    /*
    public function testGetByCreateCircularDependencyFromClosureInContainer()
    {
        $this->expectExceptionMessage('Circular dependency detected while trying to resolve entry');
        $this->expectException(\Chiron\Container\Exception\ContainerException::class);

        $container = new Container();

        $container->bind(Class1CircularDependency::class);
        $container->bind(Class2CircularDependency::class);

        $callable = function (Class1CircularDependency $class) {
            return $class;
        };

        $container->bind('circular', $callable);

        $object = $container->get('circular');

        static::assertInstanceOf(Class1CircularDependency::class, $object);
    }*/
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
