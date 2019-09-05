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
    public function build(TableOperation $operation): string
    {
        switch ($operation->getOperation()) {
            case TableOperation::CREATE:
                $definitions = [];
                foreach ($operation->getColumnOperations() as $columnOperation) {
                    $definitions[] = $this->buildColumn($columnOperation->getColumn(), $columnOperation->getOptions());
                }

                return sprintf(
                    'CREATE TABLE %s (%s);',
                    $this->buildIdentifier($operation->getTable()),
                    implode(', ', $definitions)
                );
            case TableOperation::DROP:
                return sprintf(
                    'DROP TABLE %s;',
                    $this->buildIdentifier($operation->getTable())
                );
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
            case 'uuid':
                return 'CHAR(36)';
            case 'char':
                return sprintf('CHAR(%d)', $length);
            case 'string':
                return sprintf('VARCHAR(%d)', $length);
            default:
                throw new \InvalidArgumentException('Unknown column type "%s".', $type);
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
}
