<?php

declare(strict_types=1);

namespace Chiron\Tests\Container;

use Chiron\Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use Chiron\Container\Exception\CannotResolveException;
use Wandu\Http\Parameters\ParsedBody;
use Wandu\Http\Psr\ServerRequest;

class AutoResolveTest extends TestCase
{

    public function testBind()
    {
        $container = new Container();

        $container->bind(AutoResolveTestSimpleInterface::class, AutoResolveTestSimple::class);

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

    /**
     * @expectedException Chiron\Container\Exception\CannotResolveException
     * @expectedExceptionMessage cannot resolve the "unknown" parameter
     */
    public function testResolveExceptionForDependency()
    {
        $container = new Container();

        $container->get(AutoResolveTestDependency::class);
    }

    /**
     * @expectedException Chiron\Container\Exception\CannotResolveException
     * @expectedExceptionMessage cannot resolve the "unknown" parameter
     */
    public function testResolveExceptionForClass()
    {
        $container = new Container();

        $container->get(AutoResolveTestClass::class);
    }


}

interface AutoResolveTestSimpleInterface {}
class AutoResolveTestSimple implements AutoResolveTestSimpleInterface {}

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
