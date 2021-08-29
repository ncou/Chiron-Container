<?php

declare(strict_types=1);

namespace Chiron\Container\Exception;

use Psr\Container\ContainerExceptionInterface;
use Exception;

class CircularDependencyException extends Exception implements ContainerExceptionInterface
{
    private $serviceId;
    private $path;

    public function __construct(string $serviceId, array $path, \Throwable $previous = null)
    {
        parent::__construct(sprintf('Circular dependency detected for service "%s", path: "%s".', $serviceId, implode(' -> ', $path)), 0, $previous);

        $this->serviceId = $serviceId;
        $this->path = $path;
    }

    public function getServiceId()
    {
        return $this->serviceId;
    }

    public function getPath()
    {
        return $this->path;
    }
}
