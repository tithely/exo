<?php

namespace Exo\Operation;

interface ReducingOperationInterface
{
    /**
     * Returns the name of the operation.
     *
     * @param ReducingOperationInterface $operation
     * @return ReducingOperationInterface|null
     */
    public function apply(ReducingOperationInterface $operation): ?ReducingOperationInterface;
}
