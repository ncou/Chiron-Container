<?php

declare(strict_types=1);

namespace Chiron\Container;

class Descriptor implements DescriptorInterface
{
    /** @var array */
    public $assigns = [];

    /** @var array */
    public $wires = [];

    /** @var callable[] */
    public $afterHandlers = [];

    /** @var bool */
    public $factory = false;

    /** @var bool */
    public $frozen = false;

    /**
     * {@inheritdoc}
     */
    public function assign(string $paramName, $target): DescriptorInterface
    {
        $this->assigns[$paramName] = $target;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function assignMany(array $params = []): DescriptorInterface
    {
        $this->assigns = $params + $this->assigns;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function wire(string $propertyName, $target): DescriptorInterface
    {
        $this->wires[$propertyName] = $target;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function wireMany(array $properties): DescriptorInterface
    {
        $this->wires = $properties + $this->wires;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function after(callable $handler): DescriptorInterface
    {
        $this->afterHandlers[] = $handler;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function factory(): DescriptorInterface
    {
        $this->factory = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function freeze(): DescriptorInterface
    {
        $this->frozen = true;

        return $this;
    }
}
