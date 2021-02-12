<?php

namespace Exo\Operation;

abstract class AbstractOperation implements OperationInterface
{
    /**
     * @var string
     */
    protected string $name;

    /**
     * Returns the name of the operation.
     *
     * @return string
     */
    public function getName():string {
        return $this->name;
    }

    /**
     * Returns the string representation of the operation (I.E. create, alter, replace, etc.).
     *
     * @return string
     */
    abstract public function getOperation(): string;
}
