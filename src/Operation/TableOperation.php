<?php

namespace Exo\Operation;

class TableOperation extends AbstractOperation
{
    const CREATE = 'create';
    const ALTER = 'alter';
    const DROP = 'drop';

    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $operation;

    /**
     * @var ColumnOperation[]
     */
    private $columnOperations = [];

    /**
     * TableOperation constructor.
     *
     * @param string            $table
     * @param string            $operation
     * @param ColumnOperation[] $columnOperations
     */
    public function __construct(string $table, string $operation, array $columnOperations)
    {
        $this->table = $table;
        $this->operation = $operation;
        $this->columnOperations = $columnOperations;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Returns the operation.
     *
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * Returns the column operations.
     *
     * @return ColumnOperation[]
     */
    public function getColumnOperations(): array
    {
        return $this->columnOperations;
    }
}
