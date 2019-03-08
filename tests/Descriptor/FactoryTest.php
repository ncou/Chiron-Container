<?php

namespace Wandu\DI\Descriptor;

use Chiron\Container\Container;
use PHPUnit\Framework\TestCase;
use stdClass;

class FactoryTest extends TestCase
{
    public function testBindClosureFactory()
    {
        $container = new Container();

        $container->bind('obj1', function () {
            return new stdClass();
        });

        // all same
        $object1 = $container['obj1'];
        static::assertSame($object1, $container['obj1']);
        static::assertSame($object1, $container['obj1']);
        static::assertSame($object1, $container['obj1']);

        $container->bind('obj2', function () {
            return new stdClass();
        })->factory();
        $object2 = $container['obj2'];

        // all not same
        $object2_1 = $container['obj2'];

        static::assertNotSame($object2, $object2_1);
        static::assertEquals($object2, $object2_1);

        $object2_2 = $container['obj2'];

        static::assertNotSame($object2, $object2_2);
        static::assertEquals($object2, $object2_2);
        static::assertNotSame($object2_1, $object2_2);
        static::assertEquals($object2_1, $object2_2);
    }

    public function testBindFactory()
    {
        $container = new Container();

        $container->bind(FactoryTestIF::class, FactoryTestClass::class);

        // all same
        $object1 = $container[FactoryTestIF::class];
        static::assertSame($object1, $container[FactoryTestIF::class]);
        static::assertSame($object1, $container[FactoryTestIF::class]);
        static::assertSame($object1, $container[FactoryTestIF::class]);

        // reset
        $container = new Container();

        $container
            ->bind(FactoryTestIF::class, FactoryTestClass::class)
            ->factory();
        $object2 = $container[FactoryTestIF::class];

        // all not same
        $object2_1 = $container[FactoryTestIF::class];

        static::assertNotSame($object2, $object2_1);
        static::assertEquals($object2, $object2_1);

        $object2_2 = $container[FactoryTestIF::class];

        static::assertNotSame($object2, $object2_2);
        static::assertEquals($object2, $object2_2);
        static::assertNotSame($object2_1, $object2_2);
        static::assertEquals($object2_1, $object2_2);
    }
}

interface FactoryTestIF
{
}
class FactoryTestClass implements FactoryTestIF
{
}
