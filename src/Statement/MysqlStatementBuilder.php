<?php

namespace Exo\Statement;

use Exo\Operation\ColumnOperation;
use Exo\Operation\IndexOperation;
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
            case TableOperation::CREATE:
                $definitions = [];
                foreach ($operation->getColumnOperations() as $columnOperation) {
                    $definitions[] = $this->buildColumn($columnOperation->getColumn(), $columnOperation->getOptions());
                }

                foreach ($operation->getIndexOperations() as $indexOperation) {
                    $definitions[] = 'INDEX ' . $this->buildIndex($indexOperation->getName(), $indexOperation->getColumns(), $indexOperation->getOptions());
                }

                return sprintf(
                    'CREATE TABLE %s (%s);',
                    $this->buildIdentifier($operation->getTable()),
                    implode(', ', $definitions)
                );
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

                foreach ($operation->getIndexOperations() as $indexOperation) {
                    switch ($indexOperation->getOperation()) {
                        case IndexOperation::ADD:
                            $specifications[] = sprintf(
                                'ADD INDEX %s',
                                $this->buildIndex($indexOperation->getName(), $indexOperation->getColumns(), $indexOperation->getOptions())
                            );
                            break;
                        case IndexOperation::DROP:
                            $specifications[] = sprintf(
                                'DROP INDEX %s',
                                $this->buildIdentifier($indexOperation->getName())
                            );
                            break;
                    }
                }

                return sprintf(
                    'ALTER TABLE %s %s;',
                    $this->buildIdentifier($operation->getTable()),
                    implode(', ', $specifications)
                );
            case TableOperation::DROP:
                return sprintf(
                    'DROP TABLE %s;',
                    $this->buildIdentifier($operation->getTable())
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
            case 'enum':
                return sprintf("ENUM('%s')", implode("','", $options['values']));
            case 'text':
                if (array_key_exists('length', $options)) {
                    $sizes = [
                        'LONGTEXT' => 4294967295,
                        'MEDIUMTEXT' => 16777215,
                        'TEXT' => 65535,
                        'TINYTEXT' => 255
                    ];
                    if ($options['length'] > $sizes['LONGTEXT']) {
                        throw new \InvalidArgumentException('Invalid length provided for \'text\' column type.');
                    }
                    foreach ($sizes as $name => $length) {
                        if ($options['length'] >= $length) {
                            return $name;
                        }
                    }
                }
                return 'TEXT';
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

        if ($options['null'] ?? false) {
            $definition .= ' NULL';
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

        if (array_key_exists('default', $options)) {
            $definition .= sprintf(' DEFAULT %s', $this->buildDefaultValue($options['default']));
        }

        if ($options['update'] ?? false) {
            $definition .= sprintf(' ON UPDATE %s', $options['update']);
        }

        return $definition;
    }

    /**
     * Builds an index definition.
     *
     * @param string   $index
     * @param string[] $columns
     * @param array    $options
     * @return string
     */
    protected function buildIndex(string $index, array $columns, array $options): string
    {
        $definition = sprintf(
            '%s (%s)',
            $this->buildIdentifier($index),
            implode(', ', array_map([$this, 'buildIdentifier'], $columns))
        );

        if ($options['unique'] ?? false) {
            $definition .= ' UNIQUE';
        }

        return $definition;
    }

    /**
     * Builds a default.
     *
     * @param mixed $value
     * @return string
     */
    protected function buildDefaultValue($value): string
    {
        if (is_string($value) && $value !== 'CURRENT_TIMESTAMP') {
            return sprintf('\'%s\'', $value);
        } else {
            return sprintf('%s', $value);
        }
    }
}
