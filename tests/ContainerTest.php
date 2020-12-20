<?php

declare(strict_types=1);

namespace Chiron\Tests\Container;

use Chiron\Container\Container;
use Closure;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerTest extends TestCase
{
    /**
     * Asserts that the container can add and get a service defined as shared.
     */
    public function testContainerAddsAndGetsSharedByDefault()
    {
        $container = (new Container())->defaultToShared();
        $container->bind(Foo::class);

        $this->assertTrue($container->has(Foo::class));

        $fooOne = $container->get(Foo::class);
        $fooTwo = $container->get(Foo::class);

        $this->assertInstanceOf(Foo::class, $fooOne);
        $this->assertInstanceOf(Foo::class, $fooTwo);
        $this->assertSame($fooOne, $fooTwo);
    }

    /**
     * Asserts that the container can add and get a service defined as non-shared with defaultToShared enabled.
     */
    public function testContainerAddsNonSharedWithSharedByDefault()
    {
        $container = (new Container())->defaultToShared();
        $container->bind(Foo::class, null, false);

        $this->assertTrue($container->has(Foo::class));

        $fooOne = $container->get(Foo::class);
        $fooTwo = $container->get(Foo::class);

        $this->assertInstanceOf(Foo::class, $fooOne);
        $this->assertInstanceOf(Foo::class, $fooTwo);
        $this->assertNotSame($fooOne, $fooTwo);
    }

    public function testBound()
    {
        $container = new Container();

        $container->bind(ContainerTestRenderable::class, new ContainerTestXmlRenderer());

        static::assertTrue($container->bound(ContainerTestRenderable::class)); // set by instance
        static::assertFalse($container->bound(ContainerTestJsonRenderer::class)); // class true
    }

    public function testHas()
    {
        $container = new Container();

        $container->bind(ContainerTestRenderable::class, new ContainerTestXmlRenderer());

        static::assertTrue($container->has(ContainerTestRenderable::class)); // set by instance
        static::assertTrue($container->has(ContainerTestJsonRenderer::class)); // class true
        static::assertFalse($container->has(ContainerTestServerAccessible::class)); // interface false
        static::assertFalse($container->has('Unknown\\Class')); // not defined class
    }

    public function testHasNull()
    {
        $container = new Container();

        $container->bind('null', null);

        static::assertTrue($container->has('null')); // container has null,
        static::assertFalse($container->has('undefined'));
    }

    /*
        public function testInstance()
        {
            $container = new Container();
            $xml = new ContainerTestXmlRenderer();

            $container->instance('xml', $xml);
            $container->instance('is_debug', true);

            static::assertSame($xml, $container->get('xml'));
            static::assertSame(true, $container->get('is_debug'));
        }
    */

    public function testClosure()
    {
        $container = new Container();

        $container->bind(ContainerTestRenderable::class, $renderer = new ContainerTestXmlRenderer());
        $container->singleton(ContainerTestHttpController::class, function (ContainerInterface $app) {
            return new ContainerTestHttpController($app->get(ContainerTestRenderable::class), [
                'username' => 'username string',
                'password' => 'password string',
            ]);
        });

        static::assertInstanceOf(ContainerTestHttpController::class, $container->get(ContainerTestHttpController::class));
        static::assertSame($container->get(ContainerTestHttpController::class), $container->get(ContainerTestHttpController::class));
        static::assertSame($renderer, $container->get(ContainerTestHttpController::class)->renderer);
        static::assertEquals([
            'username' => 'username string',
            'password' => 'password string',
        ], $container->get(ContainerTestHttpController::class)->config);
    }

    public function testClosureWithTypeHint()
    {
        $container = new Container();

        $container->bind(ContainerTestRenderable::class, $renderer = new ContainerTestXmlRenderer());
        $container->singleton(ContainerTestHttpController::class, function (ContainerTestRenderable $renderable) {
            return new ContainerTestHttpController($renderable, [
                'username' => 'username string',
                'password' => 'password string',
            ]);
        });

        static::assertInstanceOf(ContainerTestHttpController::class, $container->get(ContainerTestHttpController::class));
        static::assertSame($container->get(ContainerTestHttpController::class), $container->get(ContainerTestHttpController::class));
        static::assertSame($renderer, $container->get(ContainerTestHttpController::class)->renderer);
        static::assertEquals([
            'username' => 'username string',
            'password' => 'password string',
        ], $container->get(ContainerTestHttpController::class)->config);
    }

    public function testAlias()
    {
        $container = new Container();
        $renderer = new ContainerTestXmlRenderer();

        $container->bind(ContainerTestRenderable::class, $renderer);

        $container->alias('myalias', ContainerTestRenderable::class);
        $container->alias('otheralias', 'myalias');

        static::assertSame($renderer, $container->get(ContainerTestRenderable::class));
        static::assertSame($renderer, $container->get('myalias'));
        static::assertSame($renderer, $container->get('otheralias'));
    }

    public function testGetByCreate()
    {
        $container = new Container();

        $controller = $container->get(ContainerTestJsonRenderer::class);
        static::assertInstanceOf(ContainerTestJsonRenderer::class, $controller);
    }

    public function testMutation()
    {
        $container = new Container();

        $container->mutation(
            Bar::class,
            function (Bar $object) {
                $object->setValue('foobar');

                // this is to check the instance returned is not used in the container code !!!
                return new Foo();
            }
        );

        $bar = $container->get(Bar::class);
        static::assertInstanceOf(Bar::class, $bar);
        static::assertSame($bar->getValue(), 'foobar');

        $bar2 = new Bar();
        static::assertSame($bar2->getValue(), 'bar');
    }

    public function testMutationWithSharedObject()
    {
        $container = new Container();

        $container->singleton(Bar::class);

        $counter = 0;
        $container->mutation(
            Bar::class,
            function (Bar $object) use (&$counter) {
                $counter++;
            }
        );

        $bar = $container->get(Bar::class);
        static::assertSame($counter, 1);

        $bar = $container->get(Bar::class);
        static::assertSame($counter, 1);

        $bar = $container->get(Bar::class, true);
        static::assertSame($counter, 2);
    }

    /**
     * @expectedException Chiron\Container\Exception\EntityNotFoundException
     *
     * @expectedExceptionMessage Service "unknown" wasn't found in the dependency injection container
     */
    public function testGetFail()
    {
        $container = new Container();
        $container->get('unknown');
    }

    /**
     * @expectedException Chiron\Container\Exception\EntityNotFoundException
     *
     * @expectedExceptionMessage Service "unknown" wasn't found in the dependency injection container
     */
    public function testGetAliasFail()
    {
        $container = new Container();
        $container->alias('fail', 'unknown');

        $container->get('fail');
    }

    public function testGetAliasWithDefaultContainer()
    {
        $container = new Container();

        $function = function () {
            return rand();
        };

        $shared = false;
        $container->bind('randomFunction', $function, $shared);
        $container->alias('rand', 'randomFunction');

        $this->assertNotEquals($container->get('rand'), $container->get('rand'));
    }

    public function testGetAliasWithContainerDefaultToShared()
    {
        $container = new Container();
        $container->defaultToShared();

        $function = function () {
            return rand();
        };

        $shared = false;
        $container->bind('randomFunction', $function, $shared);
        $container->alias('rand', 'randomFunction');

        $this->assertNotEquals($container->get('rand'), $container->get('rand'));
    }

    public function testGetAliasSingletonClosure()
    {
        $container = new Container();

        $function = function () {
            return rand();
        };

        $shared = true;
        $container->bind('randomFunction', $function, $shared);
        $container->alias('rand', 'randomFunction');

        $this->assertEquals($container->get('rand'), $container->get('rand'));
    }

    public function testGetAliasSingletonClass()
    {
        $container = new Container();

        $bar = new Bar();
        $container->singleton(Bar::class, $bar);

        $container->alias('foo', Bar::class);

        $this->assertEquals($container->get('foo'), $bar);
    }

    /**
     * @deprecated use catchException
     *
     * @param $expected
     * @param \Closure $closure
     * @param string   $message
     */
    // TODO : à améliorer !!!!
    private static function assertException($expected, Closure $closure, $message = '')
    {
        try {
            $closure();
        } catch (\Throwable $e) {
            static::assertEquals($expected, $e, $message);

            return;
        }
        static::fail($message);
    }
}

interface ContainerTestRenderable
{
}
class ContainerTestJsonRenderer implements ContainerTestRenderable
{
}
class ContainerTestXmlRenderer implements ContainerTestRenderable
{
}

interface ContainerTestServerAccessible
{
}

class ContainerTestHttpController
{
    public function __construct(ContainerTestRenderable $renderer, array $config)
    {
        $this->renderer = $renderer;
        $this->config = $config;
    }
}

class Foo
{
}

class Bar
{
    private $value = 'bar';

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
