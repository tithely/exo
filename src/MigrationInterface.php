<?php

namespace Exo;

use Exo\Operation\OperationInterface;

interface MigrationInterface
{
    /**
     * Returns the table operation.
     *
     * @return OperationInterface
     */
    public function getOperation(): OperationInterface;
}
