<?php

declare(strict_types=1);

namespace Chiron\Container\Exception;

use Psr\Container\ContainerExceptionInterface;
use Exception;

class BindingResolutionException extends Exception implements ContainerExceptionInterface
{
}
