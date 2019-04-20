<?php

namespace Chiron\Container\Definition;

use Chiron\Container\Container;
use PHPUnit\Framework\TestCase;

class AssignTest extends TestCase
{
    /**
     * @expectedException Chiron\Container\Exception\ContainerException
     * @expectedExceptionMessage Parameter 'dep' cannot be resolved
     */
    public function testAssignFailByAssigningUnknown()
    {
        // assign unknown
        $container = new Container();
        $container->add(AssignTestClass::class)->assign('dep', 'unknown');

        $container->get(AssignTestClass::class);
    }

    public function testAssignArrayValue()
    {
        // assign unknown
        $container = new Container();
        $container->add(AssignTestClass::class)->assign('dep', ['value' => 'hello']);

        $object = $container->get(AssignTestClass::class);
        static::assertInstanceOf(AssignTestClass::class, $object);
        static::assertSame('hello', $object->getDep());
    }

    public function testAssignByBinding()
    {
        // bind interface
        $container = new Container();
        $container->add('dep_dependency', 'hello dependency!');
        $container->add(AssignTestClassIF::class, AssignTestClass::class)->assign('dep', 'dep_dependency');

        $object = $container->get(AssignTestClass::class);
        static::assertInstanceOf(AssignTestClass::class, $object);
        static::assertSame('hello dependency!', $object->getDep());

        $object = $container->get(AssignTestClassIF::class);
        static::assertInstanceOf(AssignTestClass::class, $object);
        static::assertSame('hello dependency!', $object->getDep());

        // bind class directly
        $container = new Container();
        $container->add('dep_dependency', 'hello dependency!');
        $container->add(AssignTestClass::class)->assign('dep', 'dep_dependency');

        $object = $container->get(AssignTestClass::class);
        static::assertInstanceOf(AssignTestClass::class, $object);
        static::assertSame('hello dependency!', $object->getDep());
    }

    /**
     * @expectedException Chiron\Container\Exception\ContainerException
     * @expectedExceptionMessage Parameter 'dep' cannot be resolved
     */
    public function testAssignFailByClosure()
    {
        $container = new Container();
        $container->add(AssignTestClass::class, function ($dep) {
            return new AssignTestClass($dep . ' from closure');
        });

        $container->get(AssignTestClass::class);
    }

    public function testAssignSuccessByClosure()
    {
        $container = new Container();
        $container->add('dep_dependency', 'hello dependency!');
        $container->add(AssignTestClassIF::class, function ($dep) {
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
