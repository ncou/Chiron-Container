<?php

declare(strict_types=1);

namespace Chiron\Tests\Container;

use Chiron\Container\Container;
use PHPUnit\Framework\TestCase;

class NotInstantiableTest extends TestCase
{
    public function testHas()
    {
        $container = new Container();

        static::assertFalse($container->has(ContainerTestInterface::class)); // interface
        static::assertFalse($container->has(ContainerTestAbstract::class)); // abstract class
        static::assertFalse($container->has(ContainerTestTrait::class)); // trait
        static::assertFalse($container->has(ContainerTestPrivateConstructor::class)); // private constructor
    }

    /**
     * @expectedException Chiron\Container\Exception\NullReferenceException
     * @expectedExceptionMessage it was not found;
     */
    public function testBuildInterface()
    {
        $container = new Container();

        static::assertFalse($container->build(ContainerTestInterface::class)); // interface
    }

    /**
     * @expectedException Chiron\Container\Exception\DependencyException
     * @expectedExceptionMessage cannot be resolved: the class is not instantiable
     */
    public function testBuildAbstractClass()
    {
        $container = new Container();

        static::assertFalse($container->build(ContainerTestAbstract::class)); // abstract class
    }

    /**
     * @expectedException Chiron\Container\Exception\NullReferenceException
     * @expectedExceptionMessage it was not found;
     */
    public function testBuildTrait()
    {
        $container = new Container();

        static::assertFalse($container->build(ContainerTestTrait::class)); // trait
    }

    /**
     * @expectedException Chiron\Container\Exception\DependencyException
     * @expectedExceptionMessage cannot be resolved: the class is not instantiable
     */
    public function testBuildPrivateConstructorClass()
    {
        $container = new Container();

        static::assertFalse($container->build(ContainerTestPrivateConstructor::class)); // private constructor
    }
}

interface ContainerTestInterface
{
}

abstract class ContainerTestAbstract
{
}

trait ContainerTestTrait
{
}

class ContainerTestPrivateConstructor
{
    private function __construct()
    {
    }
}
