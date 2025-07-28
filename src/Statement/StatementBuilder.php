<?php
namespace Exo\Statement;

use Exo\Operation\ExecOperation;
use Exo\Operation\FunctionOperation;
use Exo\Operation\OperationInterface;
use Exo\Operation\ProcedureOperation;
use Exo\Operation\TableOperation;
use Exo\Operation\UnsupportedOperationException;
use Exo\Operation\ViewOperation;
use InvalidArgumentException;

abstract class StatementBuilder
{
    /**
     * Builds SQL statements for an operation.
     *
     * @param OperationInterface $operation
     * @return string
     * @throws UnsupportedOperationException
     */
    public function build(OperationInterface $operation): string
    {
        $operationClass = get_class($operation);

        switch ($operationClass) {

            case TableOperation::class:
                /* @var TableOperation $operation */
                return $this->buildTable($operation);

            case ViewOperation::class:
                /* @var ViewOperation $operation */
                return $this->buildView($operation);

            case ProcedureOperation::class:
                /* @var ProcedureOperation $operation */
                return $this->buildProcedure($operation);

            case FunctionOperation::class:
                /* @var FunctionOperation $operation */
                return $this->buildFunction($operation);

            case ExecOperation::class:
                /* @var ExecOperation $operation */
                return $this->buildExecute($operation);

            default:
                throw new UnsupportedOperationException($operationClass);
        }
    }

    /**
     * Builds SQL statement for an execute operation.
     *
     * @param ExecOperation $operation
     * @return string
     */
    public function buildExecute(ExecOperation $operation): string
    {
        return $operation->getBody();
    }

    /**
     * Builds a column definition.
     *
     * @param string $definition
     * @param array $options
     * @return string
     */
    protected function buildColumn(string $definition, array $options): string
    {
        if (!($options['null'] ?? true)) {
            $definition .= ' NOT NULL';
        }

        if ($options['null'] ?? false) {
            $definition .= ' NULL';
        }

        if ($options['unique'] ?? false) {
            $definition .= ' UNIQUE';
        }

        if ($options['primary'] ?? false) {
            $definition .= ' PRIMARY KEY';
        }

        if ($options['first'] ?? false) {
            $definition .= ' FIRST';
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

    /**
     * Builds an identifier.
     *
     * @param string $identifier
     * @return string
     */
    abstract protected function buildIdentifier(string $identifier): string;

    /**
     * Builds SQL statements for an table operation.
     *
     * @param TableOperation $operation
     * @return string
     * @throws UnsupportedOperationException
     */
    abstract protected function buildTable(TableOperation $operation): string;

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
            case 'json':
                return 'JSON';
            case 'string':
                return sprintf('VARCHAR(%d)', $length);
            case 'text':
                if (array_key_exists('length', $options)) {
                    $sizes = [
                        'LONGTEXT' => 4294967295,
                        'MEDIUMTEXT' => 16777215,
                        'TEXT' => 65535,
                        'TINYTEXT' => 255
                    ];
                    if ($options['length'] > $sizes['LONGTEXT']) {
                        throw new InvalidArgumentException('Invalid length provided for \'text\' column type.');
                    }
                    foreach ($sizes as $name => $length) {
                        if ($options['length'] >= $length) {
                            return $name;
                        }
                    }
                }
                return 'TEXT';
            case 'timestamp':
                return 'TIMESTAMP';
            case 'uuid':
                return 'CHAR(36)';
            default:
                throw new InvalidArgumentException(sprintf('Unknown column type "%s".', $type));
        }
    }
}
