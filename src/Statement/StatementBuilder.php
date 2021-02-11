<?php

namespace Exo\Statement;

use Exo\Operation\FunctionOperation;
use Exo\Operation\OperationInterface;
use Exo\Operation\TableOperation;
use Exo\Operation\ViewOperation;
use InvalidArgumentException;

abstract class StatementBuilder
{
    /**
     * Builds SQL statements for an operation.
     *
     * @param OperationInterface $operation
     * @return string
     */
    abstract public function build(OperationInterface $operation): string;

    /**
     * Builds SQL statements for a table operation.
     *
     * @param TableOperation $operation
     * @return string
     */
    abstract public function buildTable(TableOperation $operation): string;

    /**
     * Builds SQL statements for a view operation.
     *
     * @param ViewOperation $operation
     * @return string
     */
    abstract public function buildView(ViewOperation $operation): string;

    /**
     * Builds SQL statements for a function operation.
     *
     * @param FunctionOperation $operation
     * @return string
     */
    abstract public function buildFunction(FunctionOperation $operation): string;

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
            default:
                throw new InvalidArgumentException(sprintf('Unknown column type "%s".', $type));
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
