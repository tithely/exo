<?php

namespace Exo;

use Exo\Operation\FunctionOperation;
use Exo\Operation\ParameterOperation;
use Exo\Operation\ReturnTypeOperation;
use Exo\Operation\VariableOperation;
use LogicException;

final class FunctionMigration implements MigrationInterface
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    private string $operation;

    /**
     * @var string|null
     */
    private ?string $body;

    /**
     * @var ReturnTypeOperation|null
     */
    private ?ReturnTypeOperation $returnType;

    /**
     * @var bool
     */
    private bool $deterministic;

    /**
     * @var string
     */
    private string $dataUse;

    /**
     * @var string
     */
    private string $language;

    /**
     * @var ParameterOperation[]
     */
    private array $parameterOperations;

    /**
     * @var VariableOperation[]
     */
    private array $variableOperations;

    /**
     * Returns a new create view migration.
     *
     * @param string $name
     * @return FunctionMigration
     */
    public static function create(string $name): FunctionMigration
    {
        return new FunctionMigration($name, FunctionOperation::CREATE);
    }

    /**
     * Returns a new create view migration.
     *
     * @param string $name
     * @return FunctionMigration
     */
    public static function replace(string $name): FunctionMigration
    {
        return new FunctionMigration($name, FunctionOperation::REPLACE);
    }

    /**
     * Returns a new drop view migration.
     *
     * @param string $name
     * @return FunctionMigration
     */
    public static function drop(string $name): FunctionMigration
    {
        return new FunctionMigration($name, FunctionOperation::DROP);
    }

    /**
     * FunctionMigration constructor.
     *
     * @param string                $name
     * @param string                $operation
     * @param ?ReturnTypeOperation  $returnType
     * @param bool                  $deterministic
     * @param string                $dataUse
     * @param string                $language
     * @param array                 $parameterOperations
     * @param array                 $variableOperations
     * @param string|null           $body
     */
    private function __construct(
        string $name,
        string $operation,
        ?ReturnTypeOperation $returnType = null,
        bool $deterministic = false,
        string $dataUse = 'READS SQL DATA',
        string $language = 'plpgsql',
        array $parameterOperations = [],
        array $variableOperations = [],
        ?string $body = null
    ) {
        $this->name = $name;
        $this->operation = $operation;
        $this->returnType = $returnType;
        $this->deterministic = $deterministic;
        $this->dataUse = $dataUse;
        $this->language = $language;
        $this->parameterOperations = $parameterOperations;
        $this->variableOperations = $variableOperations;
        $this->body = $body;
    }

    /**
     * Sets determinism for the function.
     *
     * @param bool $deterministic
     * @return $this
     */
    public function withDeterminism(bool $deterministic): FunctionMigration
    {
        if ($this->operation === FunctionOperation::DROP) {
            throw new LogicException('Cannot set deterministic property in a drop migration.');
        }

        $this->deterministic = $deterministic;

        return $this;
    }

    /**
     * Sets the reads sql data property of the function.
     *
     * @param string $dataUse
     * @return $this
     */
    public function withDataUse(string $dataUse): FunctionMigration
    {
        if ($this->operation === FunctionOperation::DROP) {
            throw new LogicException('Cannot set dataUse property in a drop migration.');
        }

        if (!in_array($dataUse, ['CONTAINS SQL', 'NO SQL', 'READS SQL DATA', 'MODIFIES SQL DATA'])) {
            throw new LogicException('Cannot set dataUse, not a valid option.');
        }

        $this->dataUse = $dataUse;

        return $this;
    }

    /**
     * Sets the language property of the function.
     *
     * @param string $language
     * @return $this
     */
    public function withLanguage(string $language): FunctionMigration
    {
        if ($this->operation === FunctionOperation::DROP) {
            throw new LogicException('Cannot set language property in a drop migration.');
        }

        if (!in_array($language, ['SQL', 'plpgsql'])) {
            throw new LogicException('Cannot set language, not a valid option.');
        }

        $this->language = $language;

        return $this;
    }

    /**
     * Sets the return type of the migration.
     *
     * @param string $type
     * @param array  $options
     * @return $this
     */
    public function withReturnType(string $type, array $options = []): FunctionMigration
    {
        if ($this->operation === FunctionOperation::DROP) {
            throw new LogicException('Cannot set a return type in a drop migration.');
        }

        $this->returnType = new ReturnTypeOperation($type, ReturnTypeOperation::ADD, $options);

        return $this;
    }

    /**
     * Pushes a new add parameter operation.
     *
     * @param string $name
     * @param array  $options
     * @return $this
     */
    public function withParameter(string $name, array $options = []): FunctionMigration
    {
        if ($this->operation === FunctionOperation::DROP) {
            throw new LogicException('Cannot add parameters in a view drop migration.');
        }

        $parameterOperations = $this->parameterOperations;

        array_push(
            $parameterOperations,
            new ParameterOperation($name, ParameterOperation::ADD, $options)
        );

        $this->parameterOperations = $parameterOperations;

        return $this;
    }

    /**
     * Pushes a new add variable operation.
     *
     * @param string $name
     * @param array  $options
     * @return $this
     */
    public function withVariable(string $name, array $options = []): FunctionMigration
    {
        if ($this->operation === FunctionOperation::DROP) {
            throw new LogicException('Cannot add variables in a view drop migration.');
        }

        $variableOperations = $this->variableOperations;

        array_push(
            $variableOperations,
            new VariableOperation($name, VariableOperation::ADD, $options)
        );

        $this->variableOperations = $variableOperations;

        return $this;
    }

    /**
     * Pushes a new add column operation.
     *
     * @param string $body
     * @return FunctionMigration
     */
    public function withBody(string $body): FunctionMigration
    {
        if ($this->operation === FunctionOperation::DROP) {
            throw new LogicException('Cannot set view body in a view drop migration.');
        }

        $this->body = $body;

        return $this;
    }

    /**
     * Returns the function operation.
     *
     * @return FunctionOperation
     * @throws LogicException
     */
    public function getOperation(): FunctionOperation
    {
        $this->validate();

        return new FunctionOperation(
            $this->name,
            $this->operation,
            $this->returnType,
            $this->deterministic,
            $this->dataUse,
            $this->language,
            $this->parameterOperations,
            $this->variableOperations,
            $this->body
        );
    }

    /**
     * Validates the migration definition.
     *
     * @throws LogicException
     */
    private function validate(): void {
        if ($this->operation !== FunctionOperation::DROP) {
            if (is_null($this->returnType)) {
                throw new LogicException('Function Migration must include a return type.');
            }
            if (is_null($this->body)) {
                throw new LogicException('Function Migration must include a body.');
            }
        }
    }
}
