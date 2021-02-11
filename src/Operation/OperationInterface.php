<?php

namespace Exo\Operation;

interface OperationInterface
{
    /**
     * Returns the name of the operation.
     *
     * @return string
     */
    public function getName():string;

    /**
     * Returns the string representation of the operation (I.E. create, alter, replace, etc.).
     *
     * @return string
     */
    public function getOperation(): string;

    /**
     * Returns the operations ability to support reduction.
     *
     * @return bool
     */
    public function getSupportsReduction(): bool;

    /**
     * Returns the operations ability to support reversal.
     *
     * @return bool
     */
    public function getSupportsReversal(): bool;
}
