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
     * Returns the reverse of the operation.
     *
     * @return static
     */
    public function reverse()
    {
        switch ($this->getOperation()) {
            case self::CREATE:
                $operation = TableOperation::DROP;
                $columns = [];
                break;
            case self::DROP:
                $operation = TableOperation::CREATE;
                $columns = [];
                break;
            default:
                $operation = $this->getOperation();
                $columns = $this->getColumnOperations();
                break;
        }

        return new TableOperation(
            $this->table,
            $operation,
            $columns
        );
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

        // Collect existing columns
        $columns = [];
        foreach ($this->columnOperations as $columnOperation) {
            $columns[$columnOperation->getColumn()] = $columnOperation;
        }

        if ($this->operation === self::CREATE) {
            if ($operation->operation === self::CREATE) {
                throw new \InvalidArgumentException('Cannot recreate an existing table.');
            }

            // Skip creation of tables that will be dropped
            if ($operation->operation === self::DROP) {
                return null;
            }

            foreach ($operation->columnOperations as $columnOperation) {
                $options = $columnOperation->getOptions();

                // Calculate column position
                $offset = count($columns);

                if ($options['first'] ?? false) {
                    $offset = 0;
                }

                if ($options['after'] ?? null) {
                    $offset = array_search($options['after'], array_keys($columns)) + 1;
                }

                // Remove existing operation for the column
                foreach ($columns as $existing => $column) {
                    if ($column->getColumn() === $columnOperation->getColumn()) {
                        unset($columns[$existing]);
                        break;
                    }
                }

                // Remove modifiers
                unset($options['first'], $options['after']);

                // Apply new column operation
                switch ($columnOperation->getOperation()) {
                    case ColumnOperation::ADD:
                    case ColumnOperation::MODIFY:
                        $addOperation = new ColumnOperation(
                            $columnOperation->getColumn(),
                            ColumnOperation::ADD,
                            $options
                        );

                        array_splice($columns, $offset, 0, [$addOperation]);
                        break;
                }
            }
        } else {
            // Skip modification of tables that will be dropped
            if ($operation->operation === self::DROP) {
                return $operation;
            }

            foreach ($operation->columnOperations as $columnOperation) {
                $originalOperation = $columnOperation->getOperation();

                // Remove existing operation for the column
                foreach ($columns as $existing => $column) {
                    if ($column->getColumn() === $columnOperation->getColumn()) {
                        unset($columns[$existing]);
                        $originalOperation = $column->getOperation();
                        break;
                    }
                }

                // Apply new column operation
                switch ($columnOperation->getOperation()) {
                    case ColumnOperation::ADD:
                    case ColumnOperation::DROP:
                        $columns[] = $columnOperation;
                        break;
                    case ColumnOperation::MODIFY:
                        $columns[] = new ColumnOperation(
                            $columnOperation->getColumn(),
                            $originalOperation,
                            $columnOperation->getOptions()
                        );
                        break;
                }
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
