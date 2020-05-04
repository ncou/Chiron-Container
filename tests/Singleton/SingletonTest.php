<?php

declare(strict_types=1);

namespace Chiron\Tests\Container\Singleton;

use Chiron\Tests\Container\Singleton\Fixtures\DeclarativeSingleton;
use Chiron\Tests\Container\Singleton\Fixtures\SampleClass;
use Chiron\Container\Container;
use PHPUnit\Framework\TestCase;
use Closure;

class SingletonTest extends TestCase
{
    public function testSingletonInstance(): void
    {
        $container = new Container();
        $container->share('sampleClass', $instance = new SampleClass());
        $this->assertSame($instance, $container->get('sampleClass'));
    }

    public function testSingletonToItself(): void
    {
        $container = new Container();
        $container->share(SampleClass::class, SampleClass::class);

        $sc = $container->get(SampleClass::class);
        $this->assertSame($sc, $container->get(SampleClass::class));
    }

    public function testSingletonInstanceWithAlias(): void
    {
        $container = new Container();
        $container->share('sampleClass', $instance = new SampleClass());
        $container->alias('binding', 'sampleClass');

        $this->assertSame($instance, $container->get('sampleClass'));
        $this->assertSame($instance, $container->get('binding'));
    }

    public function testSingletonClosure(): void
    {
        $container = new Container();

        $instance = new SampleClass();

        $container->share('sampleClass', function () use ($instance) {
            return $instance;
        });

        $this->assertSame($instance, $container->get('sampleClass'));
    }

    public function testSingletonClosureTwice(): void
    {
        $container = new Container();

        $container->share('sampleClass', function () {
            return new SampleClass();
        });

        $instance = $container->get('sampleClass');

        $this->assertInstanceOf(SampleClass::class, $instance);
        $this->assertSame($instance, $container->get('sampleClass'));
    }

    public function testSingletonFactory(): void
    {
        $container = new Container();

        $container->share('sampleClass', Closure::fromCallable([self::class, 'sampleClass']));

        $instance = $container->get('sampleClass');

        $this->assertInstanceOf(SampleClass::class, $instance);
        $this->assertSame($instance, $container->get('sampleClass'));
    }

    public function testAliasedSingleton(): void
    {
        $container = new Container();

        $container->share('sampleClass', function () {
            return new SampleClass();
        });
        $container->alias('singleton', 'sampleClass');

        $instance = $container->get('singleton');

        $this->assertInstanceOf(SampleClass::class, $instance);
        $this->assertSame($instance, $container->get('singleton'));
        $this->assertSame($instance, $container->get('sampleClass'));
    }

    public function testDeclarativeSingleton(): void
    {
        $container = new Container();

        $instance = $container->get(DeclarativeSingleton::class);

        $this->assertInstanceOf(DeclarativeSingleton::class, $instance);
        $this->assertSame($instance, $container->get(DeclarativeSingleton::class));
    }

    /**
     * @return SampleClass
     */
    private function sampleClass()
    {
        return new SampleClass();
    }

}
