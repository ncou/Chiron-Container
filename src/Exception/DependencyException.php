<?php

declare(strict_types=1);

namespace Chiron\Container\Exception;

use Psr\Container\ContainerExceptionInterface;

class DependencyException extends \Exception implements ContainerExceptionInterface
{
}
