<?php

declare(strict_types=1);

namespace Chiron\Container\Exception;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;

class EntryNotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
    /** @var string */
    protected $entry;

    /**
     * @param string $entry
     */
    public function __construct(string $entry)
    {
        $this->entry = $entry;
        $this->message = sprintf('Undefined class or binding for "%s".', $entry);
    }

    /**
     * @return string
     */
    public function getEntry(): string
    {
        return $this->entry;
    }
}
