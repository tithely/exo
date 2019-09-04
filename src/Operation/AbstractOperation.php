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
}
