<?php

namespace Exo;

use Exo\Operation\ColumnOperation;
use Exo\Operation\IndexOperation;
use Exo\Operation\TableOperation;
use LogicException;

class TableMigration implements MigrationInterface
{
    /**
     * @var string
     */
    private string $table;

    /**
     * @var string
     */
    private string $operation;

    /**
     * @var ColumnOperation[]
     */
    private array $columnOperations = [];

    /**
     * @var IndexOperation[]
     */
    private array $indexOperations = [];

    /**
     * Returns a new create table migration.
     *
     * @param string $table
     * @return Migration
     */
    public static function create(string $table): Migration
    {
        return new Migration($table, TableOperation::CREATE);
    }

    /**
     * Returns a new alter table migration.
     *
     * @param string $table
     * @return MigrationInterface
     */
    public static function alter(string $table)
    {
        return new Migration($table, TableOperation::ALTER);
    }

    /**
     * Returns a new drop table migration.
     *
     * @param string $table
     * @return MigrationInterface
     */
    public static function drop(string $table)
    {
        return new Migration($table, TableOperation::DROP);
    }

    /**
     * Migration constructor.
     *
     * @param string $table
     * @param string $operation
     */
    public function __construct(string $table, string $operation)
    {
        $this->table = $table;
        $this->operation = $operation;
    }

    /**
     * Pushes a new add column operation.
     *
     * @param string $column
     * @param array  $options
     * @return Migration
     */
    public function addColumn(string $column, array $options = []): Migration
    {
        if ($this->operation === TableOperation::DROP) {
            throw new LogicException('Cannot add columns in a drop migration.');
        }

        $this->columnOperations[] = new ColumnOperation($column, ColumnOperation::ADD, $options);

        return $this;
    }

    /**
     * Pushes a new modify column operation.
     *
     * @param string $column
     * @param array  $options
     * @return Migration
     */
    public function modifyColumn(string $column, array $options): Migration
    {
        if ($this->operation === TableOperation::CREATE) {
            throw new LogicException('Cannot modify columns in a create migration.');
        }

        if ($this->operation === TableOperation::DROP) {
            throw new LogicException('Cannot modify columns in a drop migration.');
        }

        $this->columnOperations[] = new ColumnOperation($column, ColumnOperation::MODIFY, $options);

        return $this;
    }

    /**
     * Pushes a new drop column operation.
     *
     * @param string $column
     * @return $this
     */
    public function dropColumn(string $column): self
    {
        if ($this->operation === TableOperation::CREATE) {
            throw new LogicException('Cannot drop columns in a create migration.');
        }

        if ($this->operation === TableOperation::DROP) {
            throw new LogicException('Cannot drop columns in a drop migration.');
        }

        $this->columnOperations[] = new ColumnOperation($column, ColumnOperation::DROP, []);

        return $this;
    }

    /**
     * Pushes a new add index operation.
     *
     * @param string $name
     * @param array  $columns
     * @param array  $options
     * @return $this
     */
    public function addIndex(string $name, array $columns, array $options = []): self
    {
        if ($this->operation === TableOperation::DROP) {
            throw new LogicException('Cannot add indexes in a drop migration.');
        }

        $this->indexOperations[] = new IndexOperation($name, IndexOperation::ADD, $columns, $options);

        return $this;
    }

    /**
     * Pushes a new drop index operation.
     *
     * @param string $name
     * @return $this
     */
    public function dropIndex(string $name): self
    {
        if ($this->operation === TableOperation::CREATE) {
            throw new LogicException('Cannot drop indexes in a create migration.');
        }

        if ($this->operation === TableOperation::DROP) {
            throw new LogicException('Cannot drop indexes in a drop migration.');
        }

        $this->indexOperations[] = new IndexOperation($name, IndexOperation::DROP, [], []);

        return $this;
    }

    /**
     * Returns the table operation.
     *
     * @return TableOperation
     */
    public function getOperation(): TableOperation
    {
        return new TableOperation($this->table, $this->operation, $this->columnOperations, $this->indexOperations);
    }
}
