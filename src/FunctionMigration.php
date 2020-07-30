<?php

namespace Exo;

use Exo\Operation\FunctionOperation;
use Exo\Operation\ParameterOperation;
use Exo\Operation\ReturnTypeOperation;
use Exo\Operation\VariableOperation;
use LogicException;

final class FunctionMigration
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $operation;

    /**
     * @var string|null
     */
    private $body;

    /**
     * @var ReturnTypeOperation|null
     */
    private $returnType;

    /**
     * @var bool
     */
    private $deterministic;

    /**
     * @var bool
     */
    private $readsSqlData;

    /**
     * @var ParameterOperation[]
     */
    private $parameterOperations;

    /**
     * @var VariableOperation[]
     */
    private $variableOperations;

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
     * @param string $name
     * @param string $operation
     * @param ReturnTypeOperation $returnType
     * @param bool $deterministic
     * @param bool $readsSqlData
     * @param array $parameterOperations
     * @param array $variableOperations
     * @param string|null $body
     */
    private function __construct(
        string $name,
        string $operation,
        ReturnTypeOperation $returnType = null,
        bool $deterministic = false,
        bool $readsSqlData = false,
        array $parameterOperations = [],
        array $variableOperations = [],
        string $body = null
    ) {
        $this->name = $name;
        $this->operation = $operation;
        $this->returnType = $returnType;
        $this->deterministic = $deterministic;
        $this->readsSqlData = $readsSqlData;
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
    public function isDeterministic(bool $deterministic): FunctionMigration
    {
        if ($this->operation === FunctionOperation::DROP) {
            throw new LogicException('Cannot set deterministic property in a drop migration.');
        }

        $this->deterministic = $deterministic;

        return $this;
    }

    /**
     * Sets determinism for the function.
     *
     * @param bool $readsSqlData
     * @return $this
     */
    public function readsSqlData(bool $readsSqlData): FunctionMigration
    {
        if ($this->operation === FunctionOperation::DROP) {
            throw new LogicException('Cannot set readsSqlData property in a drop migration.');
        }

        $this->readsSqlData = $readsSqlData;

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
    public function addParameter(string $name, array $options = []): FunctionMigration
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
    public function addVariable(string $name, array $options = []): FunctionMigration
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
            $this->readsSqlData,
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
