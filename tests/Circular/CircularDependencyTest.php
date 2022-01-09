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
        $container = new Container();
        $container->alias('AliasA', 'AliasA');

        try {
            $container->get('AliasA');
        } catch (CircularDependencyException $exception) {
            $this->assertInstanceOf(CircularDependencyException::class, $exception);
            $this->assertSame('Circular dependency detected for service "AliasA", path: "AliasA -> AliasA".', $exception->getMessage());
            $this->assertSame('AliasA', $exception->getServiceId());
            $this->assertSame(['AliasA', 'AliasA'], $exception->getPath());
        }
    }

    public function testGetByCreateCircularDependencyFromAlias2()
    {
        $container = new Container();
        $container->alias('AliasA', 'LinkA');
        $container->alias('LinkA', 'AliasA');

        try {
            $container->get('AliasA');
        } catch (CircularDependencyException $exception) {
            $this->assertInstanceOf(CircularDependencyException::class, $exception);
            $this->assertSame('Circular dependency detected for service "AliasA", path: "AliasA -> LinkA -> AliasA".', $exception->getMessage());
            $this->assertSame('AliasA', $exception->getServiceId());
            $this->assertSame(['AliasA', 'LinkA', 'AliasA'], $exception->getPath());
        }
    }

    public function testGetByCreateCircularDependency()
    {
        $container = new Container();

        try {
            $container->get(Class1CircularDependency::class);
        } catch (CircularDependencyException $exception) {
            $this->assertInstanceOf(CircularDependencyException::class, $exception);
            $this->assertSame('Circular dependency detected for service "Chiron\Tests\Container\Circular\Class1CircularDependency", path: "Chiron\Tests\Container\Circular\Class1CircularDependency -> Chiron\Tests\Container\Circular\Class2CircularDependency -> Chiron\Tests\Container\Circular\Class1CircularDependency".', $exception->getMessage());
            $this->assertSame('Chiron\Tests\Container\Circular\Class1CircularDependency', $exception->getServiceId());
            $this->assertSame([
                'Chiron\Tests\Container\Circular\Class1CircularDependency',
                'Chiron\Tests\Container\Circular\Class2CircularDependency',
                'Chiron\Tests\Container\Circular\Class1CircularDependency'
            ], $exception->getPath());
        }
    }

    public function testGetByCreateCircularDependencyFromContainer()
    {
        $container = new Container();

        $container->bind(Class1CircularDependency::class);
        $container->bind(Class2CircularDependency::class);

        try {
            $container->get(Class1CircularDependency::class);
        } catch (CircularDependencyException $exception) {
            $this->assertInstanceOf(CircularDependencyException::class, $exception);
            $this->assertSame('Circular dependency detected for service "Chiron\Tests\Container\Circular\Class1CircularDependency", path: "Chiron\Tests\Container\Circular\Class1CircularDependency -> Chiron\Tests\Container\Circular\Class2CircularDependency -> Chiron\Tests\Container\Circular\Class1CircularDependency".', $exception->getMessage());
            $this->assertSame('Chiron\Tests\Container\Circular\Class1CircularDependency', $exception->getServiceId());
            $this->assertSame([
                'Chiron\Tests\Container\Circular\Class1CircularDependency',
                'Chiron\Tests\Container\Circular\Class2CircularDependency',
                'Chiron\Tests\Container\Circular\Class1CircularDependency'
            ], $exception->getPath());
        }
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
