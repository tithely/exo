<?php

namespace Exo\Operation;

use InvalidArgumentException;
use LogicException;

final class TableOperation extends AbstractOperation implements ReversibleOperationInterface, ReducingOperationInterface
{
    const CREATE = 'create';
    const ALTER = 'alter';
    const DROP = 'drop';

    /**
     * @var string
     */
    private string $operation;

    /**
     * @var ColumnOperation[]
     */
    private array $columnOperations;

    /**
     * @var IndexOperation[]
     */
    private array $indexOperations;

    /**
     * TableOperation constructor.
     *
     * @param string            $name
     * @param string            $operation
     * @param ColumnOperation[] $columnOperations
     * @param IndexOperation[]  $indexOperations
     */
    public function __construct(string $name, string $operation, array $columnOperations, array $indexOperations)
    {
        $this->name = $name;
        $this->operation = $operation;
        $this->columnOperations = $columnOperations;
        $this->indexOperations = $indexOperations;
    }

    /**
     * Returns the reverse of the operation.
     *
     * @param ReversibleOperationInterface|null $originalOperation
     * @return static
     */
    public function reverse(ReversibleOperationInterface $originalOperation = null): ReversibleOperationInterface
    {
        /* @var TableOperation $originalOperation*/
        if ($this->getOperation() === TableOperation::CREATE) {
            return new TableOperation(
                $this->getName(),
                TableOperation::DROP,
                [],
                []
            );
        }

        if ($originalOperation && $originalOperation->getName() !== $this->getName()) {
            throw new InvalidArgumentException('Previous operations must apply to the same table.');
        }

        // Provide the create table operation to reverse a drop
        if ($this->getOperation() === TableOperation::DROP) {
            return $originalOperation;
        }

        $columnOperations = [];
        foreach ($this->columnOperations as $columnOperation) {
            // Find a column operation to reconstruct previous version of the column
            $originalColumn = null;
            foreach ($originalOperation->getColumnOperations() as $originalColumnOperation) {
                if ($originalColumnOperation->getName() === $columnOperation->getName()) {
                    $originalColumn = $originalColumnOperation;
                    break;
                }
            }

            if ($columnOperation->getOperation() === ColumnOperation::ADD) {
                $columnOperations[] = new ColumnOperation($columnOperation->getName(), ColumnOperation::DROP, []);
                continue;
            }

            if ($columnOperation->getOperation() === ColumnOperation::DROP) {
                if (!$originalColumn) {
                    throw new LogicException('Cannot revert a column that does not exist.');
                }

                $columnOperations[] = $originalColumn;
            }

            if ($columnOperation->getOperation() === ColumnOperation::MODIFY) {
                if (!$originalColumn) {
                    throw new LogicException('Cannot revert a column that does not exist.');
                }

                $columnOperations[] = new ColumnOperation(
                    $columnOperation->getName(),
                    ColumnOperation::MODIFY,
                    $originalColumn->getOptions()
                );
            }
        }

        $indexOperations = [];
        foreach ($this->indexOperations as $indexOperation) {
            // Find an index operation to reconstruct previous version of the index
            $originalIndex = null;
            foreach ($originalOperation->getIndexOperations() as $originalIndexOperation) {
                if ($originalIndexOperation->getName() === $indexOperation->getName()) {
                    $originalIndex = $originalIndexOperation;
                    break;
                }
            }

            if ($indexOperation->getOperation() === IndexOperation::ADD) {
                $indexOperations[] = new IndexOperation($indexOperation->getName(), IndexOperation::DROP, [], []);
                continue;
            }

            if ($indexOperation->getOperation() === IndexOperation::DROP) {
                if (!$originalIndex) {
                    throw new LogicException('Cannot revert a index that does not exist.');
                }

                $indexOperations[] = $originalIndex;
            }
        }

        return new TableOperation(
            $this->getName(),
            TableOperation::ALTER,
            $columnOperations,
            $indexOperations
        );
    }

    /**
     * Returns a new operation by applying another operation.
     *
     * @param ReducingOperationInterface $operation
     * @return TableOperation|null
     */
    public function apply(ReducingOperationInterface $operation): ?ReducingOperationInterface
    {
        /* @var TableOperation $operation */
        if ($operation->getName() !== $this->getName()) {
            throw new InvalidArgumentException('Cannot apply operations for a different table.');
        }

        if ($this->getOperation() === self::DROP) {
            throw new InvalidArgumentException('Cannot apply further operations to a dropped table.');
        }

        // Collect existing columns
        $columns = [];
        foreach ($this->getColumnOperations() as $columnOperation) {
            $columns[$columnOperation->getName()] = $columnOperation;
        }

        // Collect existing indexes
        $indexes = [];
        foreach ($this->getIndexOperations() as $indexOperation) {
            $indexes[$indexOperation->getName()] = $indexOperation;
        }

        if ($this->getOperation() === self::CREATE) {
            if ($operation->getOperation() === self::CREATE) {
                throw new InvalidArgumentException('Cannot recreate an existing table.');
            }

            // Skip creation of tables that will be dropped
            if ($operation->getOperation() === self::DROP) {
                return null;
            }

            foreach ($operation->getColumnOperations() as $columnOperation) {
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
                    if ($column->getName() === $columnOperation->getName()) {
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
                            $columnOperation->getName(),
                            ColumnOperation::ADD,
                            $options
                        );

                        array_splice($columns, $offset, 0, [$addOperation]);
                        break;
                }
            }

            foreach ($operation->getIndexOperations() as $indexOperation) {
                $options = $indexOperation->getOptions();

                // Calculate index position
                $offset = count($indexes);

                // Remove existing operation for the index
                foreach ($indexes as $existing => $index) {
                    if ($index->getName() === $indexOperation->getName()) {
                        unset($indexes[$existing]);
                        break;
                    }
                }

                // Apply new index operation
                if ($indexOperation->getOperation() === IndexOperation::ADD) {
                    $addOperation = new IndexOperation(
                        $indexOperation->getName(),
                        IndexOperation::ADD,
                        $indexOperation->getColumns(),
                        $options
                    );

                    array_splice($indexes, $offset, 0, [$addOperation]);
                    break;
                }
            }
        } else {
            // Skip modification of tables that will be dropped
            if ($operation->operation === self::DROP) {
                return $operation;
            }

            foreach ($operation->getColumnOperations() as $columnOperation) {
                $originalOperation = $columnOperation->getOperation();

                // Remove existing operation for the column
                foreach ($columns as $existing => $column) {
                    if ($column->getName() === $columnOperation->getName()) {
                        unset($columns[$existing]);
                        $originalOperation = $column->getOperation();
                        break;
                    }
                }

                // Apply new column operation
                switch ($columnOperation->getOperation()) {
                    case ColumnOperation::ADD:
                        $columns[] = $columnOperation;
                        break;
                    case ColumnOperation::DROP:
                        if ($originalOperation !== ColumnOperation::ADD) {
                            $columns[] = $columnOperation;
                        }
                        break;
                    case ColumnOperation::MODIFY:
                        $columns[] = new ColumnOperation(
                            $columnOperation->getName(),
                            $originalOperation,
                            $columnOperation->getOptions()
                        );
                        break;
                }
            }

            foreach ($operation->getIndexOperations() as $indexOperation) {
                $originalOperation = null;

                // Remove existing operations for the index
                foreach ($indexes as $existing => $index) {
                    if ($index->getName() === $indexOperation->getName()) {
                        $originalOperation = $index->getOperation();
                        unset($indexes[$existing]);
                        break;
                    }
                }

                if (
                    $indexOperation->getOperation() === IndexOperation::DROP &&
                    $originalOperation === ColumnOperation::ADD
                ) {
                    continue;
                }

                $indexes[$indexOperation->getName()] = $indexOperation;
            }
        }

        // Remove non-existent columns from indexes
        foreach ($indexes as $name => $index) {
            $indexColumns = [];

            foreach ($index->getColumns() as $indexColumn) {
                foreach ($columns as $column) {
                    if ($column->getName() === $indexColumn) {
                        $indexColumns[] = $indexColumn;
                    }
                }
            }

            if ($index->getOperation() === IndexOperation::ADD && empty($indexColumns)) {
                unset($indexes[$name]);
                continue;
            }

            $indexes[$name] = new IndexOperation(
                $index->getName(),
                $index->getOperation(),
                $indexColumns,
                $index->getOptions()
            );
        }

        return new TableOperation(
            $this->getName(),
            $this->getOperation(),
            array_values($columns),
            array_values($indexes)
        );
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

    /**
     * Returns the index operations.
     *
     * @return IndexOperation[]
     */
    public function getIndexOperations(): array
    {
        return $this->indexOperations;
    }
}
