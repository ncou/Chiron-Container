<?php

declare(strict_types=1);

namespace Chiron\Container\Exception;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;

class EntityNotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
}
