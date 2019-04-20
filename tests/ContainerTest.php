<?php

declare(strict_types=1);

namespace Chiron\Tests\Container;

use Chiron\Container\Container;
use Chiron\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /**
     * Asserts that the container can add and get a service defined as shared.
     */
    public function testContainerAddsAndGetsSharedByDefault()
    {
        $container = (new Container())->defaultToShared();
        $container->add(Foo::class);

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
        $container->add(Foo::class, null, false);

        $this->assertTrue($container->has(Foo::class));

        $fooOne = $container->get(Foo::class);
        $fooTwo = $container->get(Foo::class);

        $this->assertInstanceOf(Foo::class, $fooOne);
        $this->assertInstanceOf(Foo::class, $fooTwo);
        $this->assertNotSame($fooOne, $fooTwo);
    }

    public function testHas()
    {
        $container = new Container();

        $container->add(ContainerTestRenderable::class, new ContainerTestXmlRenderer());

        static::assertTrue($container->has(ContainerTestRenderable::class)); // set by instance
        static::assertTrue($container->has(ContainerTestJsonRenderer::class)); // class true
        static::assertFalse($container->has(ContainerTestServerAccessible::class)); // interface false
        static::assertFalse($container->has(ContainerTestHttpController::class));
        static::assertFalse($container->has('Unknown\\Class')); // not defined class

        // "has" map to offsetExists
        static::assertTrue(isset($container[ContainerTestRenderable::class]));
        static::assertTrue(isset($container[ContainerTestJsonRenderer::class]));
        static::assertFalse(isset($container[ContainerTestServerAccessible::class]));
        static::assertFalse(isset($container[ContainerTestHttpController::class]));
        static::assertFalse(isset($container['Unknown\\Class']));
    }

    public function testHasNull()
    {
        $container = new Container();

        $container->add('null', null);

        static::assertTrue($container->has('null')); // container has null,
        static::assertFalse($container->has('undefined'));

        static::assertTrue(isset($container['null']));
        static::assertFalse(isset($container['undefined']));
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

            // "get" map to offsetGet
            static::assertSame($xml, $container['xml']);
            static::assertSame(true, $container['is_debug']);
        }
    */

    public function testClosure()
    {
        $container = new Container();
        $container->add(ContainerInterface::class, $container);

        $container->add(ContainerTestRenderable::class, $renderer = new ContainerTestXmlRenderer());
        $container->share(ContainerTestHttpController::class, function (ContainerInterface $app) {
            return new ContainerTestHttpController($app[ContainerTestRenderable::class], [
                'username' => 'username string',
                'password' => 'password string',
            ]);
        });

        static::assertInstanceOf(ContainerTestHttpController::class, $container[ContainerTestHttpController::class]);
        static::assertSame($container[ContainerTestHttpController::class], $container[ContainerTestHttpController::class]);
        static::assertSame($renderer, $container[ContainerTestHttpController::class]->renderer);
        static::assertEquals([
            'username' => 'username string',
            'password' => 'password string',
        ], $container[ContainerTestHttpController::class]->config);
    }

    public function testClosureWithTypeHint()
    {
        $container = new Container();

        $container->add(ContainerTestRenderable::class, $renderer = new ContainerTestXmlRenderer());
        $container->share(ContainerTestHttpController::class, function (ContainerTestRenderable $renderable) {
            return new ContainerTestHttpController($renderable, [
                'username' => 'username string',
                'password' => 'password string',
            ]);
        });

        static::assertInstanceOf(ContainerTestHttpController::class, $container[ContainerTestHttpController::class]);
        static::assertSame($container[ContainerTestHttpController::class], $container[ContainerTestHttpController::class]);
        static::assertSame($renderer, $container[ContainerTestHttpController::class]->renderer);
        static::assertEquals([
            'username' => 'username string',
            'password' => 'password string',
        ], $container[ContainerTestHttpController::class]->config);
    }

    public function testAlias()
    {
        $container = new Container();
        $renderer = new ContainerTestXmlRenderer();

        $container->add(ContainerTestRenderable::class, $renderer);

        $container->alias('myalias', ContainerTestRenderable::class);
        $container->alias('otheralias', 'myalias');

        static::assertSame($renderer, $container[ContainerTestRenderable::class]);
        static::assertSame($renderer, $container['myalias']);
        static::assertSame($renderer, $container['otheralias']);
    }

    public function testGetByCreate()
    {
        $container = new Container();

        $controller = $container->get(ContainerTestJsonRenderer::class);
        static::assertInstanceOf(ContainerTestJsonRenderer::class, $controller);
    }

    /**
     * @expectedException Chiron\Container\Exception\EntityNotFoundException
     * @expectedExceptionMessage Service 'unknown' wasn't found in the dependency injection container
     */
    public function testGetFail()
    {
        $container = new Container();
        $container->get('unknown');
    }

    public function testDestroy()
    {
        $container = new Container();

        $container->add('xml', new ContainerTestXmlRenderer());

        static::assertTrue($container->has('xml'));

        $container->destroy('xml');

        static::assertFalse($container->has('xml'));
    }

    public function testDestroyMany()
    {
        $container = new Container();

        $container->add('xml1', new ContainerTestXmlRenderer());
        $container->add('xml2', new ContainerTestXmlRenderer());

        static::assertTrue($container->has('xml1'));
        static::assertTrue($container->has('xml2'));

        $container->destroy('xml1', 'xml2');

        static::assertFalse($container->has('xml1'));
        static::assertFalse($container->has('xml2'));
    }

    public function testGetAlias()
    {
        $container = new Container();
        $container->alias('foo', 'ConcreteStub');
        $this->assertEquals($container->getAlias('foo'), 'ConcreteStub');
        $this->assertTrue($container->isAlias('foo'));
        $this->assertFalse($container->isAlias('bar'));
    }

    /**
     * @deprecated use catchException
     *
     * @param $expected
     * @param \Closure $closure
     * @param string   $message
     */
    // TODO : à améliorer !!!!
    private static function assertException($expected, \Closure $closure, $message = '')
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
