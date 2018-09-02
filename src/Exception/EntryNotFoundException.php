<?php

namespace Chiron\Container\Exception;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;

/**
 * No entry was found in the container for the given identifier.
 */
class EntryNotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
    public function __construct($id)
    {
        parent::__construct(sprintf('Identifier "%s" is not defined in the container.', $id));
    }
}
