<?php

namespace Wandu\DI\Descriptor;

use Chiron\Container\Container;
use PHPUnit\Framework\TestCase;

class AssignTest extends TestCase
{
    /**
     * @expectedException Chiron\Container\Exception\CannotResolveException
     * @expectedExceptionMessage cannot resolve the "dep" parameter
     */
    public function testAssignFailByAssigningUnknown()
    {
        // assign unknown
        $container = new Container();
        $container->bind(AssignTestClass::class)->assign('dep', 'unknown');

        $container->get(AssignTestClass::class);
    }

    public function testAssignArrayValue()
    {
        // assign unknown
        $container = new Container();
        $container->bind(AssignTestClass::class)->assign('dep', ['value' => 'hello']);

        $object = $container->get(AssignTestClass::class);
        static::assertInstanceOf(AssignTestClass::class, $object);
        static::assertSame('hello', $object->getDep());
    }

    public function testAssignByBind()
    {
        // bind interface
        $container = new Container();
        $container->instance('dep_dependency', 'hello dependency!');
        $container->bind(AssignTestClassIF::class, AssignTestClass::class)->assign('dep', 'dep_dependency');

        $object = $container->get(AssignTestClass::class);
        static::assertInstanceOf(AssignTestClass::class, $object);
        static::assertSame('hello dependency!', $object->getDep());

        $object = $container->get(AssignTestClassIF::class);
        static::assertInstanceOf(AssignTestClass::class, $object);
        static::assertSame('hello dependency!', $object->getDep());

        // bind class directly
        $container = new Container();
        $container->instance('dep_dependency', 'hello dependency!');
        $container->bind(AssignTestClass::class)->assign('dep', 'dep_dependency');

        $object = $container->get(AssignTestClass::class);
        static::assertInstanceOf(AssignTestClass::class, $object);
        static::assertSame('hello dependency!', $object->getDep());
    }

    /**
     * @expectedException Chiron\Container\Exception\CannotResolveException
     * @expectedExceptionMessage cannot resolve the "dep" parameter
     */
    public function testAssignFailByClosure()
    {
        $container = new Container();
        $container->bind(AssignTestClass::class, function ($dep) {
            return new AssignTestClass($dep . ' from closure');
        });

        $container->get(AssignTestClass::class);
    }

    public function testAssignSuccessByClosure()
    {
        $container = new Container();
        $container->instance('dep_dependency', 'hello dependency!');
        $container->bind(AssignTestClassIF::class, function ($dep) {
            return new AssignTestClass($dep . ' from closure');
        })->assign('dep', 'dep_dependency');

        $object = $container->get(AssignTestClassIF::class);
        static::assertInstanceOf(AssignTestClass::class, $object);
        static::assertSame('hello dependency! from closure', $object->getDep());
    }
}

interface AssignTestClassIF
{
}
class AssignTestClass implements AssignTestClassIF
{
    protected $dep;

    public function __construct($dep)
    {
        $this->dep = $dep;
    }

    public function getDep()
    {
        return $this->dep;
    }
}
