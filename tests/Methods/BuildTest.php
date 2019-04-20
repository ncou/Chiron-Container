<?php

declare(strict_types=1);

namespace Chiron\Tests\Container\Methods;

use Chiron\Container\Container;
use PHPUnit\Framework\TestCase;
use stdClass;

class BuildTest extends TestCase
{
    /**
     * @expectedException Chiron\Container\Exception\ContainerException
     * @expectedExceptionMessage Parameter 'typedParam' cannot be resolved
     */
    public function testBuildAutoResolveFail()
    {
        $container = new Container();

        $container->build(BuildTestHasTypedParam::class);
    }

    public function testBuildAutoResolveSuccess()
    {
        $container = new Container();
        $container->add(BuildTestDependencyInterface::class, BuildTestDependency::class);

        $object = $container->build(BuildTestHasTypedParam::class);
        static::assertInstanceOf(BuildTestHasTypedParam::class, $object);
        static::assertInstanceOf(BuildTestDependencyInterface::class, $object->typedParam);
        static::assertInstanceOf(BuildTestDependency::class, $object->typedParam);
    }

    public function testBuildUntypedParamClassSuccess()
    {
        $container = new Container();

        // by seq array
        $object = $container->build(BuildTestHasUntypedParam::class, [['username' => 'wan2land']]);
        static::assertSame(['username' => 'wan2land'], $object->untypedParam);

        // by param name
        $object = $container->build(BuildTestHasUntypedParam::class, ['untypedParam' => ['username' => 'wan3land']]);
        static::assertSame(['username' => 'wan3land'], $object->untypedParam);
    }

    /**
     * @expectedException Chiron\Container\Exception\ContainerException
     * @expectedExceptionMessage Parameter 'untypedParam' cannot be resolved
     */
    public function testBuildUntypedParamClassFail()
    {
        $container = new Container();

        $container->build(BuildTestHasUntypedParam::class);
    }

    public function testBuildComplexParamsClassSuccess()
    {
        $container = new Container();

        // by assoc classname
        $object = $container->build(BuildTestHasTypedParam::class, [
            BuildTestDependencyInterface::class => $dep = new BuildTestDependency(),
        ]);
        static::assertSame($dep, $object->typedParam);

        // by sequential
        $object = $container->build(BuildTestHasComplexParam::class, [
            $param1 = new BuildTestDependency(),
            $param2 = new stdClass(),
        ]);
        static::assertSame($param1, $object->param1);
        static::assertSame($param2, $object->param2);
        static::assertSame('param3', $object->param3);
        static::assertSame('param4', $object->param4);

        // by assoc paramname
        $object = $container->build(BuildTestHasComplexParam::class, [
            'param1' => $param1 = new BuildTestDependency(),
            'param2' => $param2 = new stdClass(),
            'param4' => $param4 = new stdClass(),
        ]);
        static::assertSame($param1, $object->param1);
        static::assertSame($param2, $object->param2);
        static::assertSame('param3', $object->param3);
        static::assertSame($param4, $object->param4);

        // assoc with class name
        $object = $container->build(BuildTestHasComplexParam::class, [
            BuildTestDependencyInterface::class => $param1 = new BuildTestDependency(),
            'param2'                            => $param2 = new stdClass(),
            'param4'                            => $param4 = new stdClass(),
        ]);
        static::assertInstanceOf(BuildTestDependencyInterface::class, $object->param1);
        static::assertSame($param1, $object->param1);
        static::assertSame($param2, $object->param2);
        static::assertSame('param3', $object->param3);
        static::assertSame($param4, $object->param4);

        // complex
        $object = $container->build(BuildTestHasComplexParam::class, [
            $param1 = new BuildTestDependency(),
            $param2 = new stdClass(),
            'param4' => $param4 = new stdClass(),
            'param3' => $param3 = new stdClass(),
        ]);
        static::assertSame($param1, $object->param1);
        static::assertSame($param2, $object->param2);
        static::assertSame($param3, $object->param3);
        static::assertSame($param4, $object->param4);
    }

    /**
     * @expectedException Chiron\Container\Exception\ContainerException
     * @expectedExceptionMessage Parameter 'param1' cannot be resolved
     */
    public function testBuildComplexParamsClassFail_1()
    {
        $container = new Container();

        $container->build(BuildTestHasComplexParam::class);
    }

    /**
     * @expectedException Chiron\Container\Exception\ContainerException
     * @expectedExceptionMessage Parameter 'param2' cannot be resolved
     */
    public function testBuildComplexParamsClassFail_2()
    {
        $container = new Container();

        $container->add(BuildTestDependencyInterface::class, BuildTestDependency::class);

        $container->build(BuildTestHasComplexParam::class);
    }

    public function testBuildClassDependencyNotInstaciated()
    {
        $container = new Container();

        $object = $container->build(BuildTestClassInstance::class);

        static::assertSame('foo', $object->class->name);
    }

    public function testBuildClassDependencyAlreadyInstanciated()
    {
        $container = new Container();

        $container->add(BuildTestHasTypedParamString::class, new BuildTestHasTypedParamString('bar'));

        $object = $container->build(BuildTestClassInstance::class);

        static::assertSame('bar', $object->class->name);
    }
}

interface BuildTestDependencyInterface
{
}
class BuildTestDependency implements BuildTestDependencyInterface
{
}

class BuildTestHasTypedParam
{
    public function __construct(BuildTestDependencyInterface $typedParam)
    {
        $this->typedParam = $typedParam;
    }
}

class BuildTestHasUntypedParam
{
    public function __construct($untypedParam)
    {
        $this->untypedParam = $untypedParam;
    }
}

class BuildTestHasComplexParam
{
    public function __construct(BuildTestDependencyInterface $param1, $param2, $param3 = 'param3', $param4 = 'param4')
    {
        $this->param1 = $param1;
        $this->param2 = $param2;
        $this->param3 = $param3;
        $this->param4 = $param4;
    }
}

class BuildTestHasTypedParamString
{
    public function __construct(string $name = 'foo')
    {
        $this->name = $name;
    }
}

class BuildTestClassInstance
{
    public function __construct(BuildTestHasTypedParamString $class)
    {
        $this->class = $class;
    }
}
