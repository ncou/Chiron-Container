<?php

declare(strict_types=1);

namespace Chiron\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;
use InvalidArgumentException;

class EntityNotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
}
