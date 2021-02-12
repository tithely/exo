<?php

namespace Exo\Operation;

interface ReversibleOperationInterface extends OperationInterface
{
    /**
     * Returns the reverse of the operation.
     *
     * @param ReversibleOperationInterface|null $originalOperation
     * @return ReversibleOperationInterface|null
     */
    public function reverse(?ReversibleOperationInterface $originalOperation = null): ?ReversibleOperationInterface;
}
