<?php

declare(strict_types=1);

namespace Chiron\Container;

class Definition implements DefinitionInterface
{
    /** @var array */
    public $assigns = [];

    /**
     * {@inheritdoc}
     */
    public function assign(string $paramName, $target): DefinitionInterface
    {
        $this->assigns[$paramName] = $target;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function assignMany(array $params = []): DefinitionInterface
    {
        $this->assigns = $params + $this->assigns;

        return $this;
    }

    public function getAssigns(): array
    {
        return $this->convertAssign($this->assigns);
    }

    /**
     * @param array $arguments
     *
     * @return array
     */
    // TODO : code à virer
    protected function convertAssign(array $arguments): array
    {
        $argumentsToReturn = [];
        foreach ($arguments as $key => $value) {
            if (is_array($value)) {
                if (array_key_exists('value', $value)) {
                    $argumentsToReturn[$key] = $value['value'];
                }
                //} else {
            //    if ($this->container->has($value)) {
            //        $argumentsToReturn[$key] = $this->container->get($value);
            //    }
            }
        }

        return $argumentsToReturn;
    }

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var mixed
     */
    protected $concrete;

    /**
     * @var bool
     */
    protected $shared = false;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * Constructor.
     *
     * @param string $id
     * @param mixed  $concrete
     */
    public function __construct(string $id, $concrete = null)
    {
        $concrete = $concrete ?? $id;
        $this->alias = $id;
        $this->concrete = $concrete;
    }

    /**
     * {@inheritdoc}
     */
    public function setAlias(string $id): DefinitionInterface
    {
        $this->alias = $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * {@inheritdoc}
     */
    public function setShared(bool $shared = true): DefinitionInterface
    {
        $this->shared = $shared;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isShared(): bool
    {
        return $this->shared;
    }

    /**
     * {@inheritdoc}
     */
    public function getConcrete()
    {
        return $this->concrete;
    }

    /**
     * {@inheritdoc}
     */
    public function setConcrete($concrete): DefinitionInterface
    {
        $this->concrete = $concrete;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addArgument($arg): DefinitionInterface
    {
        $this->arguments[] = $arg;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addArguments(array $args): DefinitionInterface
    {
        foreach ($args as $arg) {
            $this->addArgument($arg);
        }

        return $this;
    }
}
