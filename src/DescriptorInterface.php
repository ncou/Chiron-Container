<?php

declare(strict_types=1);

namespace Chiron\Container;

interface DescriptorInterface
{
    /**
     * @param string       $paramName
     * @param string|array $target
     *
     * @return DescriptorInterface
     */
    public function assign(string $paramName, $target): DescriptorInterface;

    /**
     * @param array $params
     *
     * @return DescriptorInterface
     */
    public function assignMany(array $params = []): DescriptorInterface;

    /**
     * @param string       $propertyName
     * @param string|array $target
     *
     * @return DescriptorInterface
     */
    public function wire(string $propertyName, $target): DescriptorInterface;

    /**
     * @param array $properties
     *
     * @return DescriptorInterface
     */
    public function wireMany(array $properties): DescriptorInterface;

    /**
     * @return DescriptorInterface
     */
    public function freeze(): DescriptorInterface;

    /**
     * @return DescriptorInterface
     */
    public function factory(): DescriptorInterface;

    /**
     * @param callable $handler
     *
     * @return DescriptorInterface
     */
    public function after(callable $handler): DescriptorInterface;
}
