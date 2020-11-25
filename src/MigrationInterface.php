<?php

namespace Exo;

use Exo\Operation\AbstractOperation;

interface MigrationInterface
{
    /**
     * Returns the table operation.
     *
     * @return AbstractOperation
     */
    public function getOperation();
}
