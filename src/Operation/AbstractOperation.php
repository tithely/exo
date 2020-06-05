<?php

namespace Exo\Operation;

abstract class AbstractOperation implements OperationInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * Returns the name of the operation.
     *
     * @return string
     */
    public function getName():string {
        return $this->name;
    }

    abstract public function getOperation(): string;
}
