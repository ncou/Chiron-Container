<?php

declare(strict_types=1);

namespace Chiron\Tests\Container;

use Chiron\Container\Container;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class NotInstantiableTest extends TestCase
{
    public function testHas()
    {
        $container = new Container();

        static::assertFalse($container->has(ContainerTestInterface::class)); // interface
        static::assertTrue($container->has(ContainerTestAbstract::class)); // abstract class
        static::assertFalse($container->has(ContainerTestTrait::class)); // trait
        static::assertTrue($container->has(ContainerTestPrivateConstructor::class)); // private constructor
    }

    /**
     * @expectedException InvalidArgumentException
     *
     * @expectedExceptionMessage Entry 'Chiron\Tests\Container\ContainerTestInterface' cannot be resolved
     */
    public function testBuildInterface()
    {
        $container = new Container();

        static::assertFalse($container->build(ContainerTestInterface::class)); // interface
    }

    /**
     * @expectedException InvalidArgumentException
     *
     * @expectedExceptionMessage Entry "Chiron\Tests\Container\ContainerTestAbstract" cannot be resolved: the class is not instantiable
     */
    public function testBuildAbstractClass()
    {
        $container = new Container();

        static::assertFalse($container->build(ContainerTestAbstract::class)); // abstract class
    }

    /**
     * @expectedException InvalidArgumentException
     *
     * @expectedExceptionMessage Entry 'Chiron\Tests\Container\ContainerTestTrait' cannot be resolved
     */
    public function testBuildTrait()
    {
        $container = new Container();

        static::assertFalse($container->build(ContainerTestTrait::class)); // trait
    }

    /**
     * @expectedException InvalidArgumentException
     *
     * @expectedExceptionMessage Entry "Chiron\Tests\Container\ContainerTestPrivateConstructor" cannot be resolved: the class is not instantiable
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
