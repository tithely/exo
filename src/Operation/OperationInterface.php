<?php

namespace Exo\Operation;

interface OperationInterface
{
    /**
     * Returns the name of the operation.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns the operation.
     *
     * @return string
     */
    public function getOperation(): string;
}
