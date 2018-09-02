<?php

declare(strict_types=1);

namespace Chiron\Tests\Container;

use Chiron\Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerAwareTraitTest extends TestCase
{
    /**
     * Holds the Container instance for testing.
     *
     * @var    \Chiron\Container\ContainerAwareTrait
     */
    protected $object;

    /**
     * Setup the tests.
     *
     * @return  void
     *
     * @since   1.2
     */
    public function setUp()
    {
        $this->object = $this->getObjectForTrait('\\Chiron\\Container\\ContainerAwareTrait');
    }
    /**
     * Tear down the tests.
     *
     * @return  void
     *
     */
    public function tearDown()
    {
        $this->object = null;
    }
    /**
     * Tests calling getContainer() without a Container object set
     *
     * @return  void
     *
     * @coversDefaultClass  getContainer
     * @expectedException   \UnexpectedValueException
     */
    public function testGetContainerException()
    {
        //$this->object->getContainer();

        // retrieve protected method "getContainer" and execute it.
        $getContainerReflection = new \ReflectionMethod( get_class($this->object), 'getContainer' );
        $getContainerReflection->setAccessible(true);
        $getContainerReflection->invoke( $this->object, [] );
    }
    /**
     * Tests calling getContainer() with a Container object set
     *
     * @return  void
     *
     * @coversDefaultClass  getContainer
     */
    public function testGetContainer()
    {
        $reflection = new \ReflectionClass($this->object);
        $refProp = $reflection->getProperty('container');
        $refProp->setAccessible(true);
        $refProp->setValue($this->object, new Container);
        // retrieve protected method "getContainer" and execute it.
        $getContainerReflection = new \ReflectionMethod( get_class($this->object), 'getContainer' );
        $getContainerReflection->setAccessible(true);
        $result = $getContainerReflection->invoke( $this->object, [] );
        $this->assertInstanceOf(
            ContainerInterface::class,
            $result,
            'Validates the Container object was set.'
        );
    }

    /**
     * Tests setting a Container object
     *
     * @return  void
     *
     * @coversDefaultClass  setContainer
     */
    public function testSetContainer()
    {
        $this->object->setContainer(new Container);
        $reflection = new \ReflectionClass($this->object);
        $refProp = $reflection->getProperty('container');
        $refProp->setAccessible(true);
        $container = $refProp->getValue($this->object);
        $this->assertInstanceOf(
            ContainerInterface::class,
            $container,
            'Validates a Container object was retrieved.'
        );
    }
}
