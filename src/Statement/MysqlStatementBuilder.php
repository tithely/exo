<?php

namespace Exo\Statement;

use Exo\Operation\ColumnOperation;
use Exo\Operation\TableOperation;

class MysqlStatementBuilder extends StatementBuilder
{
    /**
     * Builds SQL statements for an operation.
     *
     * @param TableOperation $operation
     * @return string
     */
    public function build(TableOperation $operation): string
    {
        switch ($operation->getOperation()) {
            case TableOperation::ALTER:
                $specifications = [];
                foreach ($operation->getColumnOperations() as $columnOperation) {
                    switch ($columnOperation->getOperation()) {
                        case ColumnOperation::ADD:
                            $specifications[] = sprintf(
                                'ADD COLUMN %s',
                                $this->buildColumn($columnOperation->getColumn(), $columnOperation->getOptions())
                            );
                            break;
                        case ColumnOperation::MODIFY:
                            $specifications[] = sprintf(
                                'MODIFY COLUMN %s',
                                $this->buildColumn($columnOperation->getColumn(), $columnOperation->getOptions())
                            );
                            break;
                        case ColumnOperation::DROP:
                            $specifications[] = sprintf(
                                'DROP COLUMN %s',
                                $this->buildIdentifier($columnOperation->getColumn())
                            );
                            break;
                    }
                }

                return sprintf(
                    'ALTER TABLE %s %s;',
                    $this->buildIdentifier($operation->getTable()),
                    implode(', ', $specifications)
                );
            default:
                return parent::build($operation);
        }
    }

    /**
     * Builds an identifier.
     *
     * @param string $identifier
     * @return string
     */
    protected function buildIdentifier(string $identifier): string
    {
        return sprintf('`%s`', $identifier);
    }

    /**
     * Builds a data type definition.
     *
     * @param array $options
     * @return string
     */
    protected function buildType(array $options): string
    {
        $type = $options['type'] ?? 'string';

        switch ($type) {
            case 'datetime':
                return 'DATETIME';
            case 'json':
                return 'JSON';
            default:
                return parent::buildType($options);
        }
    }

    /**
     * Builds a column definition.
     *
     * @param string $column
     * @param array  $options
     * @return string
     */
    protected function buildColumn(string $column, array $options): string
    {
        $definition = sprintf('%s %s', $this->buildIdentifier($column), $this->buildType($options));

        if (!($options['null'] ?? true)) {
            $definition .= ' NOT NULL';
        }

        if ($options['primary'] ?? false) {
            $definition .= ' PRIMARY KEY';
        }

        if ($options['unique'] ?? false) {
            $definition .= ' UNIQUE';
        }

        if ($options['first'] ?? false) {
            $definition .= ' FIRST';
        }

        if ($options['after'] ?? null) {
            $definition .= sprintf(' AFTER %s', $this->buildIdentifier($options['after']));
        }

        return $definition;
    }
}
