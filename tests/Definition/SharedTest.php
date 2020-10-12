<?php

declare(strict_types=1);

namespace Chiron\Container\Definition;

use Chiron\Container\Container;
use PHPUnit\Framework\TestCase;
use stdClass;

class SharedTest extends TestCase
{
    public function testBindClosureShared()
    {
        $container = new Container();

        $container->singleton('obj1', function () {
            return new stdClass();
        });

        // all same
        $object1 = $container->get('obj1');
        static::assertSame($object1, $container->get('obj1'));
        static::assertSame($object1, $container->get('obj1'));
        static::assertSame($object1, $container->get('obj1'));

        $container->singleton('obj2', function () {
            return new stdClass();
        })->setShared(false);

        $object2 = $container->get('obj2');

        // all not same
        $object2_1 = $container->get('obj2');

        static::assertNotSame($object2, $object2_1);
        static::assertEquals($object2, $object2_1);

        $object2_2 = $container->get('obj2');

        static::assertNotSame($object2, $object2_2);
        static::assertEquals($object2, $object2_2);
        static::assertNotSame($object2_1, $object2_2);
        static::assertEquals($object2_1, $object2_2);
    }

    public function testBindShared()
    {
        $container = new Container();

        $container->singleton(SharedTestIF::class, SharedTestClass::class);

        // all same
        $object1 = $container->get(SharedTestIF::class);
        static::assertSame($object1, $container->get(SharedTestIF::class));
        static::assertSame($object1, $container->get(SharedTestIF::class));
        static::assertSame($object1, $container->get(SharedTestIF::class));

        // reset
        $container = new Container();

        $container
            ->singleton(SharedTestIF::class, SharedTestClass::class)
            ->setShared(false);
        $object2 = $container->get(SharedTestIF::class);

        // all not same
        $object2_1 = $container->get(SharedTestIF::class);

        static::assertNotSame($object2, $object2_1);
        static::assertEquals($object2, $object2_1);

        $object2_2 = $container->get(SharedTestIF::class);

        static::assertNotSame($object2, $object2_2);
        static::assertEquals($object2, $object2_2);
        static::assertNotSame($object2_1, $object2_2);
        static::assertEquals($object2_1, $object2_2);
    }
}

interface SharedTestIF
{
}
class SharedTestClass implements SharedTestIF
{
}
