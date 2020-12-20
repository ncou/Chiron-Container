<?php

declare(strict_types=1);

namespace Chiron\Container\Mutation;

interface MutationInterface
{
    /**
     * Get the type.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Get the callback.
     *
     * @return callable
     */
    public function getCallback(): callable;
}
