<?php

namespace Exo\Operation;

abstract class AbstractOperation
{
    /**
     * Returns the operation.
     *
     * @return string
     */
    abstract public function getOperation(): string;

    /**
     * Returns the reverse of the operation.
     *
     * @return static
     */
    abstract public function reverse();
}
