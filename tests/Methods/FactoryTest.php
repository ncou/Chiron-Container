<?php

declare(strict_types=1);

namespace Chiron\Tests\Container\Methods;

use Chiron\Container\Container;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testFactoryFunction()
    {
        $container = new Container();

        $container->bind('name', function () {
            return 'Taylor';
        });

        $factory = $container->factory('name');

        $this->assertEquals($container->get('name'), $factory());
    }
}
