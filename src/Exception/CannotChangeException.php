<?php

declare(strict_types=1);

namespace Chiron\Container\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class CannotChangeException extends RuntimeException implements ContainerExceptionInterface
{
    /** @var string */
    protected $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->message = "it cannot be changed; \"{$name}\".";
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
