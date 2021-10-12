<?php

namespace Exo\Statement;

use Exo\Operation\ColumnOperation;
use Exo\Operation\ExecOperation;
use Exo\Operation\FunctionOperation;
use Exo\Operation\IndexOperation;
use Exo\Operation\OperationInterface;
use Exo\Operation\ParameterOperation;
use Exo\Operation\TableOperation;
use Exo\Operation\UnsupportedOperationException;
use Exo\Operation\VariableOperation;
use Exo\Operation\ViewOperation;
use InvalidArgumentException;

class MysqlStatementBuilder extends StatementBuilder
{
    const VIEW_CREATE = 'CREATE VIEW %s AS (%s);';
    const VIEW_ALTER = 'ALTER VIEW %s AS (%s);';
    const VIEW_DROP = 'DROP VIEW %s;';
    const FUNCTION_CREATE = '
    CREATE FUNCTION %s(
        %s
    )
    RETURNS %s
    %s
    %s
    BEGIN
        %s
    
        %s
    END;';
    const FUNCTION_DROP_AND_REPLACE = '
    DROP FUNCTION %s;
    CREATE FUNCTION %s(
        %s
    )
    RETURNS %s
    %s
    %s
    BEGIN
        %s
    
        %s
    END;';
    const FUNCTION_DROP = 'DROP FUNCTION %s;';

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

        switch($operationClass) {

            case TableOperation::class:
                /* @var TableOperation $operation */
                return $this->buildTable($operation);

            case ViewOperation::class:
                /* @var ViewOperation $operation */
                return $this->buildView($operation);

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
     * Builds SQL statements for an table operation.
     *
     * @param TableOperation $operation
     * @return string
     * @throws UnsupportedOperationException
     */
    public function buildTable(TableOperation $operation): string
    {
        switch ($operation->getOperation()) {
            case TableOperation::CREATE:
                $definitions = [];
                foreach ($operation->getColumnOperations() as $columnOperation) {
                    $definitions[] = $this->buildColumn($columnOperation->getName(), $columnOperation->getOptions());
                }

                foreach ($operation->getIndexOperations() as $indexOperation) {
                    $definition = '';
                    if ($indexOperation->getOptions()['unique'] ?? false) {
                        $definition .= 'UNIQUE ';
                    }
                    $definition .= 'INDEX ' . $this->buildIndex($indexOperation->getName(), $indexOperation->getColumns(), $indexOperation->getOptions());
                    $definitions[] = $definition;
                }

                return sprintf(
                    'CREATE TABLE %s (%s);',
                    $this->buildIdentifier($operation->getName()),
                    implode(', ', $definitions)
                );
            case TableOperation::ALTER:
                $specifications = [];
                foreach ($operation->getColumnOperations() as $columnOperation) {
                    switch ($columnOperation->getOperation()) {
                        case ColumnOperation::ADD:
                            $specifications[] = sprintf(
                                'ADD COLUMN %s',
                                $this->buildColumn($columnOperation->getName(), $columnOperation->getOptions())
                            );
                            break;
                        case ColumnOperation::MODIFY:
                            $specifications[] = sprintf(
                                'MODIFY COLUMN %s',
                                $this->buildColumn($columnOperation->getName(), $columnOperation->getOptions())
                            );
                            break;
                        case ColumnOperation::CHANGE:
                            $specifications[] = sprintf(
                                'CHANGE COLUMN %s %s',
                                $this->buildIdentifier($columnOperation->getName()),
                                $this->buildColumn(
                                    $columnOperation->getOptions()['new_name'],
                                    $columnOperation->getOptions()
                                )
                            );
                            break;
                        case ColumnOperation::DROP:
                            $specifications[] = sprintf(
                                'DROP COLUMN %s',
                                $this->buildIdentifier($columnOperation->getName())
                            );
                            break;
                    }
                }

                foreach ($operation->getIndexOperations() as $indexOperation) {
                    switch ($indexOperation->getOperation()) {
                        case IndexOperation::ADD:
                            $specification = 'ADD ';
                            if ($indexOperation->getOptions()['unique'] ?? false) {
                                $specification .= 'UNIQUE ';
                            }
                            $specification .= sprintf(
                                'INDEX %s',
                                $this->buildIndex($indexOperation->getName(), $indexOperation->getColumns(), $indexOperation->getOptions())
                            );
                            $specifications[] = $specification;
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
                    $this->buildIdentifier($operation->getName()),
                    implode(', ', $specifications)
                );
            case TableOperation::DROP:
                return sprintf(
                    'DROP TABLE %s;',
                    $this->buildIdentifier($operation->getName())
                );
            default:
                throw new UnsupportedOperationException($operation->getOperation());
        }
    }

    /**
     * Builds SQL statements for a view operation.
     *
     * @param ViewOperation $operation
     * @return string
     * @throws UnsupportedOperationException
     */
    public function buildView(ViewOperation $operation): string
    {
        switch ($operation->getOperation()) {
            case ViewOperation::CREATE:
                return sprintf(
                    self::VIEW_CREATE,
                    $this->buildIdentifier($operation->getName()),
                    $operation->getBody()
                );
            case ViewOperation::ALTER:
                return sprintf(
                    self::VIEW_ALTER,
                    $this->buildIdentifier($operation->getName()),
                    $operation->getBody()
                );
            case ViewOperation::DROP:
                return sprintf(
                    self::VIEW_DROP,
                    $this->buildIdentifier($operation->getName())
                );
            default:
                throw new UnsupportedOperationException($operation->getOperation());
        }
    }

    /**
     * Builds SQL statements for a custom function operation.
     *
     * @param FunctionOperation $operation
     * @return string
     * @throws UnsupportedOperationException
     */
    public function buildFunction(FunctionOperation $operation): string
    {
        $parameters = array_map(function(ParameterOperation $parameterOperation) {
            return sprintf(
                '%s %s',
                $parameterOperation->getName(),
                $this->buildType($parameterOperation->getOptions())
            );
        }, $operation->getParameterOperations());

        $returnType = $this->buildType($operation->getReturnType()->getOptions());

        $determinism = ($operation->getDeterministic())
            ? 'DETERMINISTIC'
            : 'NOT DETERMINISTIC';

        $readsSqlData = ($operation->getReadsSqlData())
            ? 'READS SQL DATA'
            : '';

        $variables = array_map(function(VariableOperation $variableOperation) {
            return sprintf(
                'DECLARE %s %s;',
                $variableOperation->getName(),
                $this->buildType($variableOperation->getOptions())
            );
        }, $operation->getVariableOperations());

        switch ($operation->getOperation()) {
            case FunctionOperation::CREATE:
                return sprintf(
                    self::FUNCTION_CREATE,
                    $this->buildIdentifier($operation->getName()), // NAME (for create)
                    implode(',', $parameters), // PARAMETERS
                    $returnType, // RETURN TYPE
                    $determinism, // 'DETERMINISTIC' | 'NOT DETERMINISTIC'
                    $readsSqlData, // 'READS SQL DATA' | ''
                    implode('', $variables), // VARIABLES
                    $operation->getBody() // BODY
                );
            case FunctionOperation::REPLACE:
                return sprintf(
                    self::FUNCTION_DROP_AND_REPLACE,
                    $this->buildIdentifier($operation->getName()), // NAME (for drop)
                    $this->buildIdentifier($operation->getName()), // NAME (for create)
                    implode(',', $parameters), // PARAMETERS
                    $returnType, // RETURN TYPE
                    $determinism, // 'DETERMINISTIC' | 'NOT DETERMINISTIC'
                    $readsSqlData, // 'READS SQL DATA' | ''
                    implode('', $variables), // VARIABLES
                    $operation->getBody() // BODY
                );
            case FunctionOperation::DROP:
                return sprintf(
                    self::FUNCTION_DROP,
                    $this->buildIdentifier($operation->getName())
                );
            default:
                throw new UnsupportedOperationException($operation->getOperation());
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
                        throw new InvalidArgumentException('Invalid length provided for \'text\' column type.');
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

        if (array_key_exists('default', $options)) {
            $definition .= sprintf(' DEFAULT %s', $this->buildDefaultValue($options['default']));
        }

        if ($options['update'] ?? false) {
            $definition .= sprintf(' ON UPDATE %s', $options['update']);
        }

        if ($options['after'] ?? null) {
            $definition .= sprintf(' AFTER %s', $this->buildIdentifier($options['after']));
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
        return sprintf(
            '%s (%s)',
            $this->buildIdentifier($index),
            implode(', ', array_map([$this, 'buildIdentifier'], $columns))
        );
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
