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
     * Returns the operations ability to support reduction.
     *
     * @return bool
     */
    public function getSupportsReduction(): bool {
        return in_array(ReducingOperationInterface::class, class_implements($this));
    }

    /**
     * Returns the operations ability to support reversal.
     *
     * @return bool
     */
    public function getSupportsReversal(): bool {
        return in_array(ReversibleOperationInterface::class, class_implements($this));
    }

    /**
     * Returns the string representation of the operation (I.E. create, alter, replace, etc.).
     *
     * @return string
     */
    abstract public function getOperation(): string;
}
