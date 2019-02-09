<?php

declare(strict_types=1);

namespace Chiron\Tests\Container;

use Chiron\Container\Container;
use PHPUnit\Framework\TestCase;
use LogicException;

class ContainerTest extends TestCase
{
    public function testWithString()
    {
        $container = new Container();
        $container['param'] = 'value';
        $this->assertEquals('value', $container['param']);
    }

    /*
    public function testWithClosure()
    {
        $container = new Container();
        $container['service'] = function () {
            return new Fixtures\Service();
        };
        $this->assertInstanceOf('Simplex\Tests\Fixtures\Service', $container['service']);
    }*/
    /*
    public function testServicesShouldBeDifferent()
    {
        $container = new Container();
        $container['service'] = $container->factory(function () {
            return new Fixtures\Service();
        });
        $serviceOne = $container['service'];
        $this->assertInstanceOf('Simplex\Tests\Fixtures\Service', $serviceOne);
        $serviceTwo = $container['service'];
        $this->assertInstanceOf('Simplex\Tests\Fixtures\Service', $serviceTwo);
        $this->assertNotSame($serviceOne, $serviceTwo);
    }*/
    /*
    public function testShouldPassContainerAsParameter()
    {
        $container = new Container();
        $container['service'] = function () {
            return new Fixtures\Service();
        };
        $container['container'] = function ($container) {
            return $container;
        };
        $this->assertNotSame($container, $container['service']);
        $this->assertSame($container, $container['container']);
    }*/
    /*
    public function testIsset()
    {
        $container = new Container();
        $container['param'] = 'value';
        $container['service'] = function () {
            return new Fixtures\Service();
        };
        $container['null'] = null;
        $this->assertTrue(isset($container['param']));
        $this->assertTrue(isset($container['service']));
        $this->assertTrue(isset($container['null']));
        $this->assertFalse(isset($container['non_existent']));
    }*/
    public function testConstructorInjection()
    {
        $params = ['param' => 'value'];
        $container = new Container($params);
        $this->assertSame($params['param'], $container['param']);
    }

    /**
     * @expectedException \Chiron\Container\Exception\EntryNotFoundException
     * @expectedExceptionMessage Identifier "foo" is not defined in the container.
     */
    public function testOffsetGetValidatesKeyIsPresent()
    {
        $container = new Container();
        echo $container['foo'];
    }

    public function testOffsetGetHonorsNullValues()
    {
        $container = new Container();
        $container['foo'] = null;
        $this->assertNull($container['foo']);
    }

    /*
    public function testUnset()
    {
        $container = new Container();
        $container['param'] = 'value';
        $container['service'] = function () {
            return new Fixtures\Service();
        };
        unset($container['param'], $container['service']);
        $this->assertFalse(isset($container['param']));
        $this->assertFalse(isset($container['service']));
    }*/
    /**
     * @dataProvider serviceDefinitionProvider
     */
    /*
    public function testShare($service)
    {
        $container = new Container();
        $container['shared_service'] = $service;
        $serviceOne = $container['shared_service'];
        $this->assertInstanceOf('Simplex\Tests\Fixtures\Service', $serviceOne);
        $serviceTwo = $container['shared_service'];
        $this->assertInstanceOf('Simplex\Tests\Fixtures\Service', $serviceTwo);
        $this->assertSame($serviceOne, $serviceTwo);
    }*/

    /**
     * @dataProvider serviceDefinitionProvider
     */
    /*
    public function testProtect($service)
    {
        $container = new Container();
        $container['protected'] = $container->protect($service);
        $this->assertSame($service, $container['protected']);
    }*/

    /*
        public function testGlobalFunctionNameAsParameterValue()
        {
            $container = new Container();
            $container['global_function'] = 'strlen';
            $this->assertSame('strlen', $container['global_function']);
        }

        public function testRaw()
        {
            $container = new Container();
            $container['service'] = $definition = $container->factory(function () {
                return 'foo';
            });
            $this->assertSame($definition, $container->raw('service'));
        }

        public function testRawHonorsNullValues()
        {
            $container = new Container();
            $container['foo'] = null;
            $this->assertNull($container->raw('foo'));
        }

        public function testRawReturnsFactoryForAlreadyCreatedObject()
        {
            $container = new Container();
            $container['service'] = $definition = function () {
                return 'foo';
            };
            $this->assertEquals('foo', $container->get('service'));
            $this->assertSame($definition, $container->raw('service'));
        }
    */
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Identifier "foo" is not defined.
     */
    /*
    public function testRawValidatesKeyIsPresent()
    {
        $container = new Container();
        $container->raw('foo');
    }*/

    /**
     * @dataProvider serviceDefinitionProvider
     */
    /*
    public function testExtend($service)
    {
        $container = new Container();
        $container['shared_service'] = function () {
            return new Fixtures\Service();
        };
        $container['factory_service'] = $container->factory(function () {
            return new Fixtures\Service();
        });
        $container->extend('shared_service', $service);
        $serviceOne = $container['shared_service'];
        $this->assertInstanceOf('Simplex\Tests\Fixtures\Service', $serviceOne);
        $serviceTwo = $container['shared_service'];
        $this->assertInstanceOf('Simplex\Tests\Fixtures\Service', $serviceTwo);
        $this->assertSame($serviceOne, $serviceTwo);
        $this->assertSame($serviceOne->value, $serviceTwo->value);
        $container->extend('factory_service', $service);
        $serviceOne = $container['factory_service'];
        $this->assertInstanceOf('Simplex\Tests\Fixtures\Service', $serviceOne);
        $serviceTwo = $container['factory_service'];
        $this->assertInstanceOf('Simplex\Tests\Fixtures\Service', $serviceTwo);
        $this->assertNotSame($serviceOne, $serviceTwo);
        $this->assertNotSame($serviceOne->value, $serviceTwo->value);
    }
    public function testExtendDoesNotLeakWithFactories()
    {
        if (extension_loaded('container')) {
            $this->markTestSkipped('Simplex extension does not support this test');
        }
        $container = new Container();
        $container['foo'] = $container->factory(function () {
        });
        $container['foo'] = $container->extend('foo', function ($foo, $container) {
        });
        unset($container['foo']);
        $p = new \ReflectionProperty($container, 'values');
        $p->setAccessible(true);
        $this->assertEmpty($p->getValue($container));
        $p = new \ReflectionProperty($container, 'factories');
        $p->setAccessible(true);
        $this->assertCount(0, $p->getValue($container));
    }*/

    /**
     * TODO : attention j'ai viré le @ devant les 2 lignes ci dessous pour éviter une erreur car on a mis en commentaire le reste du test
     * expectedException \InvalidArgumentException
     * expectedExceptionMessage Identifier "foo" is not defined.
     */
    /*
    public function testExtendValidatesKeyIsPresent()
    {
        $container = new Container();
        $container->extend('foo', function () {
        });
    }*/
    /*
        public function testExtendAllowsExtendingScalar()
        {
            $container = new Container();
            $container['foo'] = 'bar';
            $container->extend('foo', function ($previous) {
                return $previous . 'bar';
            });
            $this->assertEquals('barbar', $container['foo']);
        }
    */
    // TEST
    public function testKeys()
    {
        $container = new Container();
        $container['foo'] = 123;
        $container['bar'] = 123;
        $this->assertEquals(['foo', 'bar'], $container->keys());
    }

    /** @test */
    /*
    public function settingAnInvokableObjectShouldTreatItAsFactory()
    {
        $container = new Container();
        $container['invokable'] = new Fixtures\Invokable();
        $this->assertInstanceOf('Simplex\Tests\Fixtures\Service', $container['invokable']);
    }*/
    /** @test */
    /*
    public function settingNonInvokableObjectShouldTreatItAsParameter()
    {
        $container = new Container();
        $container['non_invokable'] = new Fixtures\NonInvokable();
        $this->assertInstanceOf('Simplex\Tests\Fixtures\NonInvokable', $container['non_invokable']);
    }*/

    /**
     * @dataProvider badServiceDefinitionProvider
     * @expectedException \TypeError
     */
    // TODO : test à virer cela ne sert pas à grand chose !!!!
    public function testFactoryFailsForInvalidServiceDefinitions($service)
    {
        $container = new Container();
        $container->factory($service);
    }

    /**
     * @dataProvider badServiceDefinitionProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Callable is not a Closure or invokable object.
     */
    /*
    public function testProtectFailsForInvalidServiceDefinitions($service)
    {
        $container = new Container();
        $container->protect($service);
    }*/

    /**
     * @dataProvider badServiceDefinitionProvider
     */
    /*
    public function testExtendNonInvokableDefinition($service)
    {
        $container = new Container();
        $container['foo'] = $service;
        $container->extend('foo', function ($previous) {
            return $previous;
        });
        $this->assertSame($service, $container['foo']);
    }*/

    /**
     * @dataProvider badServiceDefinitionProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Extension service definition is not a Closure or invokable object.
     */
    /*
    public function testExtendFailsForInvalidServiceDefinitions($service)
    {
        $container = new Container();
        $container['foo'] = function () {
        };
        $container->extend('foo', $service);
    }*/

    /**
     * Provider for invalid service definitions.
     */
    public function badServiceDefinitionProvider()
    {
        return [
            [123],
            [new Fixtures\NonInvokable()],
        ];
    }

    /**
     * Provider for service definitions.
     */
    /*
    public function serviceDefinitionProvider()
    {
        return array(
            array(function ($value) {
                $service = new Fixtures\Service();
                $service->value = $value;
                return $service;
            }),
            array(new Fixtures\Invokable()),
        );
    }*/
    public function testDefiningNewServiceAfterFreeze()
    {
        $container = new Container();
        $container['foo'] = function () {
            return 'foo';
        };
        $foo = $container['foo'];
        $container['bar'] = function () {
            return 'bar';
        };
        $this->assertSame('bar', $container['bar']);
    }

    /**
     * TODO : attention j'ai viré le @ devant les 2 lignes ci dessous pour éviter une erreur car on a mis en commentaire le reste du test
     * expectedException \RuntimeException
     * expectedExceptionMessage Cannot override frozen service "foo".
     */
    /*
    public function testOverridingServiceAfterFreeze()
    {
        $container = new Container();
        $container['foo'] = function () {
            return 'foo';
        };
        $foo = $container['foo'];
        $container['foo'] = function () {
            return 'bar';
        };
    }*/

    /*
        public function testRemovingServiceAfterFreeze()
        {
            $container = new Container();
            $container['foo'] = function () {
                return 'foo';
            };
            $foo = $container['foo'];
            unset($container['foo']);
            $container['foo'] = function () {
                return 'bar';
            };
            $this->assertSame('bar', $container['foo']);
        }
    */
    /*
    public function testExtendingService()
    {
        $container = new Container();
        $container['foo'] = function () {
            return 'foo';
        };
        $container['foo'] = $container->extend('foo', function ($foo, $app) {
            return "$foo.bar";
        });
        $container['foo'] = $container->extend('foo', function ($foo, $app) {
            return "$foo.baz";
        });
        $this->assertSame('foo.bar.baz', $container['foo']);
    }

    public function testExtendingServiceAfterOtherServiceFreeze()
    {
        $container = new Container();
        $container['foo'] = function () {
            return 'foo';
        };
        $container['bar'] = function () {
            return 'bar';
        };
        $foo = $container['foo'];
        $container['bar'] = $container->extend('bar', function ($bar, $app) {
            return "$bar.baz";
        });
        $this->assertSame('bar.baz', $container['bar']);
    }
*/
    public function testGet()
    {
        $container = new Container();
        $container['param'] = 'value';
        $this->assertEquals('value', $container->get('param'));
    }

    public function testHas()
    {
        $container = new Container();
        $container['param'] = 'value';
        $this->assertTrue($container->has('param'));
        $this->assertFalse($container->has('foo'));
    }

    /*
        public function testDelegateLookupFeature()
        {
            $root = new Container();
            $container = new Container([], [], $root);
            $container['self'] = function ($c) {
                return $c;
            };
            $this->assertSame($root, $container['self']);
        }
    */
    public function testSet()
    {
        $container = new Container();
        $container->set('param', 'value');
        $this->assertEquals('value', $container['param']);
    }

    // TETS NCOU :


    public function testAliases()
    {
        $container = new Container;
        $container['foo'] = 'bar';
        $container->alias('baz', 'foo');
        $container->alias('bat', 'baz');
        $this->assertEquals('bar', $container->get('foo'));
        $this->assertEquals('bar', $container->get('baz'));
        $this->assertEquals('bar', $container->get('bat'));
    }

    public function testAliasCheckViaArrayAccess()
    {
        $container = new Container;
        $container['object'] = 'foobar';

        $container->alias('alias', 'object');

        $this->assertTrue(isset($container['alias']));
        $this->assertEquals('foobar', $container['alias']);
    }

    public function testGetAlias()
    {
        $container = new Container;
        $container->alias('foo', 'ConcreteStub');
        $this->assertEquals($container->getAlias('foo'), 'ConcreteStub');
    }
    public function testItThrowsExceptionWhenAbstractIsSameAsAlias()
    {
        $container = new Container;
        $container->alias('name', 'name');
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('[name] is aliased to itself.');
        $container->getAlias('name');
    }



}
