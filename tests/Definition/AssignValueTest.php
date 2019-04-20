<?php

namespace Chiron\Container\Definition;

use Chiron\Container\Container;
use PHPUnit\Framework\TestCase;

class AssignValueTest extends TestCase
{
    public function testAssignValueByBinding()
    {
        // bind interface
        $container = new Container();
        $container->add(AssignValueTestClassIF::class, AssignValueTestClass::class)->assign('dep', ['value' => 'hello dependency!']);

        $object = $container->get(AssignValueTestClass::class);
        static::assertInstanceOf(AssignValueTestClass::class, $object);
        static::assertSame('hello dependency!', $object->getDep());

        $object = $container->get(AssignValueTestClassIF::class);
        static::assertInstanceOf(AssignValueTestClass::class, $object);
        static::assertSame('hello dependency!', $object->getDep());

        // bind class directly
        $container = new Container();
        $container->add(AssignValueTestClass::class)->assign('dep', ['value' => 'hello dependency!']);

        $object = $container->get(AssignValueTestClass::class);
        static::assertInstanceOf(AssignValueTestClass::class, $object);
        static::assertSame('hello dependency!', $object->getDep());
    }

    public function testAssignValueByClosure()
    {
        $container = new Container();
        $container->add(AssignValueTestClassIF::class, function ($dep) {
            return new AssignValueTestClass($dep . ' from closure');
        })->assign('dep', ['value' => 'hello dependency!']);

        $object = $container->get(AssignValueTestClassIF::class);
        static::assertInstanceOf(AssignValueTestClass::class, $object);
        static::assertSame('hello dependency! from closure', $object->getDep());
    }
}

interface AssignValueTestClassIF
{
}
class AssignValueTestClass implements AssignValueTestClassIF
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
