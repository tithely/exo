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

class PostgresqlStatementBuilder extends StatementBuilder
{
    const VIEW_CREATE = 'CREATE OR REPLACE VIEW %s AS %s;';
    const VIEW_DROP = 'DROP VIEW %s;';

    const PROCEDURE_CREATE = 'CREATE OR REPLACE PROCEDURE %s(%s%s%s)
    LANGUAGE %s
    AS $$
    BEGIN
        %s
    END;
    $$';
    const PROCEDURE_DROP = 'DROP PROCEDURE %s;';

    const FUNCTION_CREATE = 'CREATE OR REPLACE FUNCTION %s(%s) RETURNS %s
    LANGUAGE %s
    AS $$
    DECLARE
    %s
    BEGIN
        %s
    END;
    $$';
    const FUNCTION_DROP = 'DROP FUNCTION %s;';

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

        $language = $operation->getLanguage();

        $variables = array_map(function (VariableOperation $variableOperation) {
            return sprintf(
                '%s %s;',
                $variableOperation->getName(),
                $this->buildType($variableOperation->getOptions())
            );
        }, $operation->getVariableOperations());

        switch ($operation->getOperation()) {
            case FunctionOperation::CREATE:
            case FunctionOperation::REPLACE:
                return sprintf(
                    self::FUNCTION_CREATE,
                    $this->buildIdentifier($operation->getName()),
                    implode(',', $parameters),
                    $returnType,
                    $language,
                    implode("\n", $variables),
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

        $language = $operation->getLanguage();

        switch ($operation->getOperation()) {
            case ProcedureOperation::CREATE:
                return sprintf(
                    self::PROCEDURE_CREATE,
                    $this->buildIdentifier($operation->getName()),
                    implode(',', $inParameters),
                    (count($inParameters) && count($outParameters) ? ', ' : ''),
                    implode(',', $outParameters),
                    $language,
                    $operation->getBody()
                );
            case ProcedureOperation::DROP:
                return sprintf(
                    self::PROCEDURE_DROP,
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
            case ViewOperation::ALTER:
                return sprintf(
                    self::VIEW_CREATE,
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
     * Modify a column definition.
     *
     * @param string $column
     * @param array $options
     * @return string
     */
    protected function modifyColumn(string $column, array $options): string
    {
        $definition = sprintf('%s TYPE %s', $this->buildIdentifier($column), $this->buildType($options));

        if (array_key_exists('using', $options)) {
            $definition .= sprintf(' USING (%s::%s)', $column, $options['using']);
        }

        if (!($options['null'] ?? true)) {
            $definition .= sprintf(', ALTER COLUMN %s SET NOT NULL', $this->buildIdentifier($column));
        }

        if (array_key_exists('default', $options)) {
            $definition .= sprintf(', ALTER COLUMN %s SET DEFAULT %s', $this->buildIdentifier($column), $this->buildDefaultValue($options['default']));
        }

        return $definition;
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

        return parent::buildColumn($definition, $options);
    }

    /**
     * Builds an identifier.
     *
     * @param string $identifier
     * @return string
     */
    protected function buildIdentifier(string $identifier): string
    {
        return sprintf('"%s"', $identifier);
    }

    /**
     * Builds an index definition.
     *
     * @param string $index
     * @param string $table
     * @param string[] $columns
     * @param array $options
     * @return string
     */
    protected function buildIndex(string $index, string $table, array $columns, array $options): string
    {
        return sprintf(
            '%s ON %s (%s)',
            $this->buildIdentifier($this->buildIndexName($index, $table)),
            $this->buildIdentifier($table),
            implode(', ', array_map([$this, 'buildIdentifier'], $columns))
        );
    }

    /**
     * Builds an index name to be unique per table.
     *
     * @param string $index
     * @param string $table
     * @param string[] $columns
     * @param array $options
     * @return string
     */
    private function buildIndexName(string $index, string $table): string
    {
        return sprintf('%s_%s_idx', $index, $table);
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
                $indices = [];
                foreach ($operation->getColumnOperations() as $columnOperation) {
                    $definitions[] = $this->buildColumn($columnOperation->getName(), $columnOperation->getOptions());
                    if (array_key_exists('unique', $columnOperation->getOptions()) || array_key_exists('primary', $columnOperation->getOptions())) {
                        $indices[] = sprintf(
                            'CREATE UNIQUE INDEX %s;',
                            $this->buildIndex($columnOperation->getName(), $operation->getName(), [$columnOperation->getName()], [])
                        );
                    }
                }
                foreach ($operation->getIndexOperations() as $indexOperation) {
                    $index = 'CREATE ';
                    if ($indexOperation->getOptions()['unique'] ?? false) {
                        $index .= 'UNIQUE ';
                    }
                    $index .= sprintf(
                        'INDEX %s;',
                        $this->buildIndex($indexOperation->getName(), $operation->getName(), $indexOperation->getColumns(), $indexOperation->getOptions())
                    );
                    $indices[] = $index;
                }

                $definition = sprintf(
                    'CREATE TABLE %s (%s);',
                    $this->buildIdentifier($operation->getName()), implode(', ', $definitions),
                );
                $index = (count($indices)) ? implode(' ', $indices) : '';
                return "$definition $index";
            case TableOperation::ALTER:
                $specifications = [];
                $indices = [];
                foreach ($operation->getColumnOperations() as $columnOperation) {
                    $columnOptions = $columnOperation->getOptions();
                    if (array_key_exists('primary', $columnOptions)) {
                        $specifications[] = sprintf(
                            'ADD PRIMARY KEY (%s)',
                            $this->buildIdentifier($columnOperation->getName())
                        );
                        unset($columnOptions['primary']);
                    }
                    if (array_key_exists('unique', $columnOptions) || array_key_exists('primary', $columnOptions)) {
                        $indices[] = sprintf(
                            'CREATE UNIQUE INDEX %s;',
                            $this->buildIndex($columnOperation->getName(), $operation->getName(), [$columnOperation->getName()], [])
                        );
                        unset($columnOptions['unique']);
                    }
                    switch ($columnOperation->getOperation()) {
                        case ColumnOperation::ADD:
                            $specifications[] = sprintf(
                                'ADD COLUMN %s',
                                $this->buildColumn($columnOperation->getName(), $columnOptions)
                            );
                            break;
                        case ColumnOperation::MODIFY:
                            $specifications[] = sprintf(
                                'ALTER COLUMN %s',
                                $this->modifyColumn($columnOperation->getName(), $columnOptions)
                            );
                            break;
                        case ColumnOperation::DROP:
                            $specifications[] = sprintf(
                                'DROP COLUMN %s CASCADE',
                                $this->buildIdentifier($columnOperation->getName())
                            );
                            break;
                    }
                }
                foreach ($operation->getIndexOperations() as $indexOperation) {
                    switch ($indexOperation->getOperation()) {
                        case IndexOperation::ADD:
                            $index = 'CREATE ';
                            if ($indexOperation->getOptions()['unique'] ?? false) {
                                $index .= 'UNIQUE ';
                            }
                            $index .= sprintf(
                                'INDEX %s;',
                                $this->buildIndex($indexOperation->getName(), $operation->getName(), $indexOperation->getColumns(), $indexOperation->getOptions())
                            );
                            $indices[] = $index;
                            break;
                        case IndexOperation::DROP:
                            $indices[] = sprintf(
                                'DROP INDEX %s;',
                                $this->buildIdentifier($this->buildIndexName($indexOperation->getName(), $operation->getName()))
                            );
                            break;
                    }
                }

                $definition = '';
                if (count($specifications)) {
                    $definition = sprintf(
                        'ALTER TABLE %s %s;',
                        $this->buildIdentifier($operation->getName()),
                        implode(', ', $specifications)
                    );
                }
                $index = (count($indices)) ? implode(' ', $indices) : '';
                return "$definition $index";
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
        $length = $options['length'] ?? 255;

        switch ($type) {
            case 'bool':
                return 'SMALLINT';
            // NOT SUPPORTED BY PGSQL, DEFAULT TO TIMESTAMP
            case 'datetime':
                return 'TIMESTAMP';
            // NOT SUPPORTED BY PGSQL, DEFAULT TO VARCHAR(255)
            case 'enum':
                return sprintf('VARCHAR(%d)', $length);
            case 'serial':
                return 'SERIAL';
            case 'text':
                return 'TEXT';
            default:
                return parent::buildType($options);
        }
    }
}
