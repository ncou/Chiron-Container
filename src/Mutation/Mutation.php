<?php

declare(strict_types=1);

namespace Chiron\Container\Mutation;

class Mutation implements MutationInterface
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * Construct.
     *
     * @param string   $type
     * @param callable $callback
     */
    public function __construct(string $type, callable $callback)
    {
        $this->type = $type;
        $this->callback = $callback;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCallback(): callable
    {
        return $this->callback;
    }
}
