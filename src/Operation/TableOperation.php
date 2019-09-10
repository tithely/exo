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
     * @param TableOperation[] $previous
     * @return static
     */
    public function reverse(array $previous = [])
    {
        if ($this->getOperation() === TableOperation::CREATE) {
            return new TableOperation(
                $this->table,
                TableOperation::DROP,
                []
            );
        }

        // Reconstruct the previous state by reducing the table's history
        $original = array_shift($previous);
        if ($original->table !== $this->table) {
            throw new \InvalidArgumentException('Previous operations must apply to the same table.');
        }

        while ($change = array_shift($previous)) {
            $original = $original->apply($change);
        }

        // Provide the create table operation to reverse a drop
        if ($this->getOperation() === TableOperation::DROP) {
            return $original;
        }

        $columnOperations = [];
        foreach ($this->columnOperations as $columnOperation) {
            // Find a column operation to reconstruct previous version of the column
            $originalColumn = null;
            foreach ($original->getColumnOperations() as $originalOperation) {
                if ($originalOperation->getColumn() === $columnOperation->getColumn()) {
                    $originalColumn = $originalOperation;
                    break;
                }
            }

            if ($columnOperation->getOperation() === ColumnOperation::ADD) {
                $columnOperations[] = new ColumnOperation($columnOperation->getColumn(), ColumnOperation::DROP, []);
                continue;
            }

            if ($columnOperation->getOperation() === ColumnOperation::DROP) {
                if (!$originalColumn) {
                    throw new \LogicException('Cannot revert a column that does not exist.');
                }

                $columnOperations[] = $originalColumn;
            }

            if ($columnOperation->getOperation() === ColumnOperation::MODIFY) {
                if (!$originalColumn) {
                    throw new \LogicException('Cannot revert a column that does not exist.');
                }

                $columnOperations[] = new ColumnOperation(
                    $columnOperation->getColumn(),
                    ColumnOperation::MODIFY,
                    $originalColumn->getOptions()
                );
            }
        }

        return new TableOperation(
            $this->table,
            TableOperation::ALTER,
            $columnOperations
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
