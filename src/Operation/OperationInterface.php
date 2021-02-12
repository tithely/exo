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
}
