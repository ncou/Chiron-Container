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
        $this->expectExceptionMessage('Circular dependency detected for service "AliasA", path: "AliasA -> AliasA".');
        $this->expectException(CircularDependencyException::class);

        $container = new Container();

        $container->alias('AliasA', 'AliasA');

        $object = $container->get('AliasA');
    }

    public function testGetByCreateCircularDependencyFromAlias2()
    {
        $this->expectExceptionMessage('Circular dependency detected for service "AliasA", path: "AliasA -> LinkA -> AliasA".');
        $this->expectException(CircularDependencyException::class);

        $container = new Container();

        $container->alias('AliasA', 'LinkA');
        $container->alias('LinkA', 'AliasA');

        $object = $container->get('AliasA');
    }

    public function testGetByCreateCircularDependency()
    {
        $this->expectExceptionMessage('Circular dependency detected for service "Chiron\Tests\Container\Circular\Class1CircularDependency", path: "Chiron\Tests\Container\Circular\Class1CircularDependency -> Chiron\Tests\Container\Circular\Class2CircularDependency -> Chiron\Tests\Container\Circular\Class1CircularDependency".');
        $this->expectException(CircularDependencyException::class);

        $container = new Container();

        $object = $container->get(Class1CircularDependency::class);
        static::assertInstanceOf(Class1CircularDependency::class, $object);
    }

    public function testGetByCreateCircularDependencyFromContainer()
    {
        $this->expectExceptionMessage('Circular dependency detected for service "Chiron\Tests\Container\Circular\Class1CircularDependency", path: "Chiron\Tests\Container\Circular\Class1CircularDependency -> Chiron\Tests\Container\Circular\Class2CircularDependency -> Chiron\Tests\Container\Circular\Class1CircularDependency".');
        $this->expectException(CircularDependencyException::class);

        $container = new Container();

        $container->bind(Class1CircularDependency::class);
        $container->bind(Class2CircularDependency::class);

        $object = $container->get(Class1CircularDependency::class);

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
