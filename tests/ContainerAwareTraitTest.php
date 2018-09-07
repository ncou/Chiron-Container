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
     * @var \Chiron\Container\ContainerAwareTrait
     */
    protected $object;

    /**
     * Setup the tests.
     *
     */
    protected function setUp()
    {
        $this->object = $this->getObjectForTrait('\\Chiron\\Container\\ContainerAwareTrait');
    }

    /**
     * Tear down the tests.
     */
    protected function tearDown()
    {
        $this->object = null;
    }

    /**
     * Tests setting a Container object.
     *
     *
     * @coversDefaultClass  setContainer
     */
    public function testSetContainer()
    {
        $this->object->setContainer(new Container());
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
