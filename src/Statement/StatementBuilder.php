<?php

namespace Exo\Statement;

use Exo\Operation\TableOperation;

abstract class StatementBuilder
{
    /**
     * Builds SQL statements for an operation.
     *
     * @param TableOperation $operation
     * @return string
     */
    abstract public function build(TableOperation $operation): string;

    /**
     * Builds an identifier.
     *
     * @param string $identifier
     * @return string
     */
    protected function buildIdentifier(string $identifier): string
    {
        return $identifier;
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
        $length = $options['length'] ?? 255;

        switch ($type) {
            case 'bool':
                return 'SMALLINT(1)';
            case 'char':
                return sprintf('CHAR(%d)', $length);
            case 'date':
                return 'DATE';
            case 'decimal':
                return sprintf('DECIMAL(%d, %d)', $options['precision'] ?? 10, $options['scale'] ?? 0);
            case 'integer':
                return 'INTEGER';
            case 'string':
                return sprintf('VARCHAR(%d)', $length);
            case 'timestamp':
                return 'TIMESTAMP';
            case 'uuid':
                return 'CHAR(36)';
            case 'enum':
                return sprintf("ENUM('%s')", implode("','", $options['values']));
            default:
                throw new \InvalidArgumentException(sprintf('Unknown column type "%s".', $type));
        }
    }

    /**
     * Builds a column definition.
     *
     * @param string $column
     * @param array  $options
     * @return string
     */
    abstract protected function buildColumn(string $column, array $options): string;

    /**
     * Builds an index definition.
     *
     * @param string   $index
     * @param string[] $columns
     * @param array    $options
     * @return string
     */
    abstract protected function buildIndex(string $index, array $columns, array $options): string;
}
