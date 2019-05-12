<?php

declare(strict_types=1);

namespace Chiron\Tests\Container\Methods;

use Chiron\Container\Container;
use Closure;
use PHPUnit\Framework\TestCase;
use stdClass;

class CallAdvancedTest extends TestCase
{
    public function testCallWithDependencies()
    {
        $container = new Container();
        $result = $container->call(function (stdClass $foo, $bar = []) {
            return func_get_args();
        });
        $this->assertInstanceOf(stdClass::class, $result[0]);
        $this->assertEquals([], $result[1]);
        $result = $container->call(function (stdClass $foo, $bar = []) {
            return func_get_args();
        }, ['bar' => 'taylor']);
        $this->assertInstanceOf(stdClass::class, $result[0]);
        $this->assertEquals('taylor', $result[1]);
        $stub = new ContainerConcreteStub();
        $result = $container->call(function (stdClass $foo, ContainerConcreteStub $bar) {
            return func_get_args();
        }, [ContainerConcreteStub::class => $stub]);
        $this->assertInstanceOf(stdClass::class, $result[0]);
        $this->assertSame($stub, $result[1]);
        /*
         * Wrap a function...
         */
        $result = $container->wrap(function (stdClass $foo, $bar = []) {
            return func_get_args();
        }, ['bar' => 'taylor']);
        $this->assertInstanceOf(Closure::class, $result);
        $result = $result();
        $this->assertInstanceOf(stdClass::class, $result[0]);
        $this->assertEquals('taylor', $result[1]);
    }

    /*
    // TODO : regarder si ce test que tu as rajouté est utile !!!!
        public function testClosureCallWithInjectedDependency()
        {
            $container = new Container();
            $result = $container->call(function (ContainerConcreteStub $stub) {
            }, ['foo' => 'bar']);

            $result = $container->call(function (ContainerConcreteStub $stub) {
            }, ['foo' => 'bar', 'stub' => new ContainerConcreteStub()]);
        }
    */

    public function testCallWithCallableArray()
    {
        $container = new Container();
        $stub = new ContainerTestCallStub();
        $result = $container->call([$stub, 'work'], ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function testCallWithStaticMethodNameString()
    {
        $container = new Container();
        $result = $container->call('Chiron\Tests\Container\Methods\ContainerStaticMethodStub::inject');
        $this->assertInstanceOf(ContainerConcreteStub::class, $result[0]);
        $this->assertEquals('taylor', $result[1]);
    }

    public function testCallWithGlobalMethodName()
    {
        $container = new Container();
        $result = $container->call('Chiron\Tests\Container\Methods\containerTestInject');
        $this->assertInstanceOf(ContainerConcreteStub::class, $result[0]);
        $this->assertEquals('taylor', $result[1]);
    }

    /**
     * @expectedException Chiron\Container\Exception\ContainerException
     * @expectedExceptionMessage Parameter 'name' cannot be resolved
     */
    public function testCallWithoutBoundClassThrowEception()
    {
        $container = new Container();

        $result = $container->call(AssignTestClass::class . '@getName');
    }

    public function testCallWithBoundClass()
    {
        $container = new Container();

        $container->add(AssignTestClass::class)->assign('name', ['value' => 'foo']);

        $result = $container->call(AssignTestClass::class . '@getName');
        $this->assertEquals('foo', $result);

        $result = $container->call(AssignTestClass::class . '@concatName', ['bar']);
        $this->assertEquals('foobar', $result);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage (ContainerTestCallStub) is not resolvable.
     */
    public function testCallWithAtSignBasedClassReferencesWithoutMethodThrowsException()
    {
        // TODO : bout de code dans le container à améliorer !!!!
        $container = new Container();
        $container->call('ContainerTestCallStub');
    }

    public function testCallWithAtSignBasedClassReferences()
    {
        $container = new Container();
        $result = $container->call(ContainerTestCallStub::class . '@work', ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $result);
        $container = new Container();
        $result = $container->call(ContainerTestCallStub::class . '@inject');
        $this->assertInstanceOf(ContainerConcreteStub::class, $result[0]);
        $this->assertEquals('taylor', $result[1]);
        $container = new Container();
        $result = $container->call(ContainerTestCallStub::class . '@inject', ['default' => 'foo']);
        $this->assertInstanceOf(ContainerConcreteStub::class, $result[0]);
        $this->assertEquals('foo', $result[1]);
        $container = new Container();
        $result = $container->call(ContainerTestCallStub::class, ['foo', 'bar'], 'work');
        $this->assertEquals(['foo', 'bar'], $result);
    }
}

class ContainerConcreteStub
{
    //
}

class ContainerTestCallStub
{
    public function work()
    {
        return func_get_args();
    }

    public function inject(ContainerConcreteStub $stub, $default = 'taylor')
    {
        return func_get_args();
    }

    public function unresolvable($foo, $bar)
    {
        return func_get_args();
    }
}

class ContainerStaticMethodStub
{
    public static function inject(ContainerConcreteStub $stub, $default = 'taylor')
    {
        return func_get_args();
    }
}

function containerTestInject(ContainerConcreteStub $stub, $default = 'taylor')
{
    return func_get_args();
}

class AssignTestClass
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function concatName($complement)
    {
        return $this->name . $complement;
    }
}
