<?php

namespace Exo\Operation;

use InvalidArgumentException;

final class FunctionOperation extends AbstractOperation implements ReversibleOperationInterface, ReducingOperationInterface
{
    const CREATE = 'create';
    const REPLACE = 'replace';
    const DROP = 'drop';

    /**
     * @var string
     */
    private string $operation;

    /**
     * @var ReturnTypeOperation
     */
    private ReturnTypeOperation $returnType;

    /**
     * @var bool
     */
    private bool $deterministic;

    /**
     * @var bool
     */
    private bool $readsSqlData;

    /**
     * @var ParameterOperation[]
     */
    private array $parameterOperations;

    /**
     * @var VariableOperation[]
     */
    private array $variableOperations;

    /**
     * @var string|null
     */
    private ?string $body;

    /**
     * FunctionOperation constructor.
     *
     * @param string $name
     * @param string $operation
     * @param ReturnTypeOperation|null $returnType
     * @param bool $deterministic
     * @param bool $readsSqlData
     * @param array $parameterOperations
     * @param array $variableOperations
     * @param string|null $body
     */
    public function __construct(
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
        $this->returnType = $returnType ?? new ReturnTypeOperation('string', ReturnTypeOperation::ADD);
        $this->deterministic = $deterministic;
        $this->readsSqlData = $readsSqlData;
        $this->parameterOperations = $parameterOperations;
        $this->variableOperations = $variableOperations;
        $this->body = $body;
    }

    /**
     * Returns the reverse of the operation.
     *
     * @param ReversibleOperationInterface|null $originalOperation
     * @return static
     */
    public function reverse(ReversibleOperationInterface $originalOperation = null): ReversibleOperationInterface
    {
        /* @var FunctionOperation $originalOperation */
        if (!is_null($originalOperation) && $originalOperation->getName() !== $this->getName()) {
            throw new InvalidArgumentException('Previous operations must apply to the same function.');
        }

        if ($this->getOperation() === FunctionOperation::CREATE) {
            return new FunctionOperation(
                $this->getName(),
                TableOperation::DROP
            );
        }

        return $originalOperation;
    }

    /**
     * Returns a new operation by applying another operation.
     *
     * @param ReducingOperationInterface $operation
     * @return ReducingOperationInterface|null
     */
    public function apply(ReducingOperationInterface $operation): ?ReducingOperationInterface
    {
        /* @var FunctionOperation $operation */
        if ($operation->getName() !== $this->getName()) {
            throw new InvalidArgumentException('Cannot apply operations for a different name.');
        }

        if ($this->getOperation() === self::DROP) {
            throw new InvalidArgumentException('Cannot apply further operations to a dropped name.');
        }

        // Skip creation of functions that will be dropped
        if (in_array($this->getOperation(), [self::CREATE, self::REPLACE])) {
            if ($operation->operation === self::DROP) {
                return null;
            }
        }

        return $operation;
    }

    /**
     * Returns the operation.
     *
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * Returns the return type.
     *
     * @return ReturnTypeOperation|null
     */
    public function getReturnType(): ?ReturnTypeOperation
    {
        return $this->returnType;
    }

    /**
     * Returns the deterministic flag.
     *
     * @return bool
     */
    public function getDeterministic(): bool
    {
        return $this->deterministic;
    }

    /**
     * Returns the readsSqlData flag.
     *
     * @return bool
     */
    public function getReadsSqlData(): bool
    {
        return $this->readsSqlData;
    }

    /**
     * Returns the parameter operations.
     *
     * @return ParameterOperation[]
     */
    public function getParameterOperations(): array
    {
        return $this->parameterOperations;
    }

    /**
     * Returns the parameter operations.
     *
     * @return VariableOperation[]
     */
    public function getVariableOperations(): array
    {
        return $this->variableOperations;
    }

    /**
     * Returns the SQL body.
     *
     * @return string|null
     */
    public function getBody(): ?string
    {
        return $this->body;
    }
}
