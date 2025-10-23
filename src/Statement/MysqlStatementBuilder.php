<?php
namespace Exo\Statement;

use Exo\Operation\ColumnOperation;
use Exo\Operation\FunctionOperation;
use Exo\Operation\IndexOperation;
use Exo\Operation\ParameterOperation;
use Exo\Operation\ProcedureOperation;
use Exo\Operation\TableOperation;
use Exo\Operation\UnsupportedOperationException;
use Exo\Operation\VariableOperation;
use Exo\Operation\ViewOperation;

class MysqlStatementBuilder extends StatementBuilder
{
    const VIEW_CREATE = 'CREATE VIEW %s AS (%s);';
    const VIEW_ALTER = 'ALTER VIEW %s AS (%s);';
    const VIEW_DROP = 'DROP VIEW %s;';

    const PROCEDURE_CREATE = 'CREATE PROCEDURE %s(%s%s%s)
    %s
    %s
    BEGIN
        %s
    END;';
    const PROCEDURE_DROP = 'DROP PROCEDURE %s;';

    const FUNCTION_CREATE = 'CREATE FUNCTION %s(%s) RETURNS %s
    %s
    %s
    BEGIN
        %s
        
        %s
    END;';
    const FUNCTION_DROP = 'DROP FUNCTION %s;';
    const FUNCTION_DROP_AND_REPLACE = self::FUNCTION_DROP . "\n" . self::FUNCTION_CREATE;

    /**
     * Builds SQL statements for a custom function operation.
     *
     * @param FunctionOperation $operation
     * @return string
     * @throws UnsupportedOperationException
     */
    public function buildFunction(FunctionOperation $operation): string
    {
        $parameters = array_map(function (ParameterOperation $parameterOperation) {
            return sprintf(
                '%s %s',
                $parameterOperation->getName(),
                $this->buildType($parameterOperation->getOptions())
            );
        }, $operation->getParameterOperations());

        $returnType = $this->buildType($operation->getReturnType()->getOptions());

        $determinism = ($operation->getDeterminism()) ? 'DETERMINISTIC' : 'NOT DETERMINISTIC';

        $dataUse = $operation->getDataUse();

        $variables = array_map(function (VariableOperation $variableOperation) {
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
                    $this->buildIdentifier($operation->getName()),
                    implode(',', $parameters),
                    $returnType,
                    $determinism,
                    $dataUse,
                    implode('', $variables),
                    $operation->getBody()
                );
            case FunctionOperation::REPLACE:
                return sprintf(
                    self::FUNCTION_DROP_AND_REPLACE,
                    $this->buildIdentifier($operation->getName()),
                    $this->buildIdentifier($operation->getName()),
                    implode(',', $parameters),
                    $returnType,
                    $determinism,
                    $dataUse,
                    implode('', $variables),
                    $operation->getBody()
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
     * Builds SQL statements for a procedure operation.
     *
     * @param ProcedureOperation $operation
     * @return string
     * @throws UnsupportedOperationException
     */
    public function buildProcedure(ProcedureOperation $operation): string
    {
        $inParameters = array_map(function (ParameterOperation $parameterOperation) {
            return sprintf(
                'IN %s %s',
                $parameterOperation->getName(),
                $this->buildType($parameterOperation->getOptions())
            );
        }, $operation->getInParameterOperations());

        $outParameters = array_map(function (ParameterOperation $parameterOperation) {
            return sprintf(
                'OUT %s %s',
                $parameterOperation->getName(),
                $this->buildType($parameterOperation->getOptions())
            );
        }, $operation->getOutParameterOperations());

        $determinism = ($operation->getDeterminism()) ? 'DETERMINISTIC' : 'NOT DETERMINISTIC';

        $dataUse = $operation->getDataUse();

        switch ($operation->getOperation()) {
            case ProcedureOperation::CREATE:
                return sprintf(
                    self::PROCEDURE_CREATE,
                    $this->buildIdentifier($operation->getName()),
                    implode(',', $inParameters),
                    (count($inParameters) && count($outParameters) ? ', ' : ''),
                    implode(',', $outParameters),
                    $determinism,
                    $dataUse,
                    $operation->getBody()
                );
            case ProcedureOperation::DROP:
                return sprintf(self::PROCEDURE_DROP, $this->buildIdentifier($operation->getName()));
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
     * Builds a column definition.
     *
     * @param string $column
     * @param array $options
     * @return string
     */
    protected function buildColumn(string $column, array $options): string
    {
        $definition = sprintf('%s %s', $this->buildIdentifier($column), $this->buildType($options));

        if ($options['auto_increment'] ?? false) {
            $definition .= ' AUTO_INCREMENT';
        }

        $definition =  parent::buildColumn($definition, $options);

        if ($options['after'] ?? null) {
            $definition .= sprintf(' AFTER %s', $this->buildIdentifier($options['after']));
        }

        return $definition;
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
     * Builds an index definition.
     *
     * @param string $index
     * @param string[] $columns
     * @param array $options
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
     * Builds SQL statements for an table operation.
     *
     * @param TableOperation $operation
     * @return string
     * @throws UnsupportedOperationException
     */
    protected function buildTable(TableOperation $operation): string
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
                    $this->buildIdentifier($operation->getName()), implode(', ', $definitions)
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
                    $this->buildIdentifier($operation->getName()), implode(', ', $specifications)
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
            case 'enum':
                return sprintf("ENUM('%s')", implode("','", $options['values']));
            // NOT SUPPORTED BY MYSQL, DEFAULT TO INTEGER
            case 'serial':
                return 'INTEGER';
            default:
                return parent::buildType($options);
        }
    }
}
