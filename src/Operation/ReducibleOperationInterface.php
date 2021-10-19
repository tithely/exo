<?php

namespace Exo\Operation;

interface ReducibleOperationInterface extends OperationInterface
{
    /**
     * Returns a new operation by applying another operation.
     *
     * @param ReducibleOperationInterface $operation
     * @return ReducibleOperationInterface|null
     */
    public function apply(ReducibleOperationInterface $operation): ?ReducibleOperationInterface;
}
