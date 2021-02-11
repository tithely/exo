<?php

namespace Exo\Operation;

interface ReversibleOperationInterface
{
    /**
     * Returns the name of the operation.
     *
     * @param ReversibleOperationInterface|null $originalOperation
     * @return ReversibleOperationInterface|null
     */
    public function reverse(?ReversibleOperationInterface $originalOperation): ReversibleOperationInterface;
}
