<?php

declare(strict_types=1);

namespace Chiron\Tests\Container;

use Chiron\Container\Container;
use Chiron\Container\Exception\BindingResolutionException;
use PHPUnit\Framework\TestCase;

class AutoResolveTest extends TestCase
{
    public function testBinding()
    {
        $container = new Container();

        $container->singleton(AutoResolveTestSimpleInterface::class, AutoResolveTestSimple::class);

        $instance1 = $container->get(AutoResolveTestSimple::class);
        $instance2 = $container->get(AutoResolveTestSimpleInterface::class);

        static::assertInstanceOf(AutoResolveTestSimple::class, $instance1);
        static::assertInstanceOf(AutoResolveTestSimpleInterface::class, $instance1);

        static::assertSame($instance1, $instance2);
    }

    public function testBindOptionalParameter()
    {
        $container = new Container();

        $instance = $container->get(AutoResolveOptionalDependency::class);

        static::assertInstanceOf(AutoResolveOptionalDependency::class, $instance);
    }

    public function testResolveExceptionForDependency()
    {
        $this->expectExceptionMessage('The service "Chiron\Tests\Container\AutoResolveTestDependency" cannot be resolved: missing required value for parameter "$unknown"');
        $this->expectException(BindingResolutionException::class);

        $container = new Container();
        $container->get(AutoResolveTestDependency::class);
    }

    public function testResolveExceptionForClass()
    {
        $this->expectExceptionMessage('The service "Chiron\Tests\Container\AutoResolveTestDependency" cannot be resolved: missing required value for parameter "$unknown"');
        $this->expectException(BindingResolutionException::class);

        $container = new Container();
        $container->get(AutoResolveTestClass::class);
    }
}

interface AutoResolveTestSimpleInterface
{
}
class AutoResolveTestSimple implements AutoResolveTestSimpleInterface
{
}

class AutoResolveTestDependency
{
    public function __construct(UnknownDepend $unknown)
    {
    }
}

class AutoResolveTestClass
{
    public function __construct(AutoResolveTestDependency $depth1)
    {
    }
}

class AutoResolveOptionalDependency
{
    public function __construct(string $known = 'foobar')
    {
    }
}
