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
     * Returns a new operation by applying another operation.
     *
     * @param TableOperation $operation
     * @return TableOperation|null
     */
    public function apply(TableOperation $operation)
    {
        if ($operation->table !== $this->table) {
            throw new \InvalidArgumentException('Cannot apply operations for a different table.');
        }

        if ($this->operation === self::DROP) {
            throw new \InvalidArgumentException('Cannot apply further operations to a dropped table.');
        }

        if ($this->operation === self::CREATE) {
            if ($operation->operation === self::CREATE) {
                throw new \InvalidArgumentException('Cannot recreate an existing table.');
            }

            // Skip creation of tables that will be dropped
            if ($operation->operation === self::DROP) {
                return null;
            }

            $columns = [];
            foreach ($this->columnOperations as $columnOperation) {
                $columns[$columnOperation->getColumn()] = $columnOperation;
            }

            foreach ($operation->columnOperations as $columnOperation) {
                // Calculate column position
                $offset = count($columns);

                if ($columnOperation->getOptions()['first'] ?? false) {
                    $offset = 0;
                }

                if ($columnOperation->getOptions()['after'] ?? null) {
                    $offset = array_search($columnOperation->getOptions()['after'], array_keys($columns));
                }

                // Remove existing operation for the column
                foreach ($columns as $existing => $column) {
                    if ($column->getColumn() === $columnOperation->getColumn()) {
                        unset($columns[$existing]);
                        break;
                    }
                }

                // Apply new column operation
                switch ($columnOperation->getOperation()) {
                    case ColumnOperation::ADD:
                        array_splice($columns, $offset, 0, [$columnOperation->getColumn() => $columnOperation]);
                        break;
                    case ColumnOperation::MODIFY:
                        $addOperation = new ColumnOperation(
                            $columnOperation->getColumn(),
                            ColumnOperation::ADD,
                            $columnOperation->getOptions()
                        );

                        array_splice($columns, $offset, 0, [$columnOperation->getColumn() => $addOperation]);
                        break;
                }
            }
        } else {
            // Skip modification of tables that will be dropped
            if ($operation->operation === self::DROP) {
                return $operation;
            }
        }

        return new TableOperation($this->table, $this->operation, array_values($columns));
    }

    /**
     * Returns the table name.
     *
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
