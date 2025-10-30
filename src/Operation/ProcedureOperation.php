<?php

namespace Exo\Operation;

use InvalidArgumentException;

final class ProcedureOperation extends AbstractOperation implements ReversibleOperationInterface, ReducibleOperationInterface
{
    const CREATE = 'create';
    const DROP = 'drop';

    /**
     * @var string
     */
    private string $operation;

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
    private array $inParameterOperations;

    /**
     * @var ParameterOperation[]
     */
    private array $outParameterOperations;

    /**
     * @var string|null
     */
    private ?string $body;

    /**
     * ProcedureOperation constructor.
     *
     * @param string      $name
     * @param string      $operation
     * @param bool        $deterministic
     * @param string      $dataUse
     * @param string      $language
     * @param array       $inParameterOperations
     * @param array       $outParameterOperations
     * @param string|null $body
     */
    public function __construct(
        string $name,
        string $operation,
        bool $deterministic = false,
        string $dataUse = 'READS SQL DATA',
        string $language = 'plpgsql',
        array $inParameterOperations = [],
        array $outParameterOperations = [],
        ?string $body = null
    ) {
        $this->name = $name;
        $this->operation = $operation;
        $this->deterministic = $deterministic;
        $this->dataUse = $dataUse;
        $this->language = $language;
        $this->inParameterOperations = $inParameterOperations;
        $this->outParameterOperations = $outParameterOperations;
        $this->body = $body;
    }

    /**
     * Returns the reverse of the operation.
     *
     * @param ReversibleOperationInterface|null $originalOperation
     * @return ReversibleOperationInterface|null
     */
    public function reverse(?ReversibleOperationInterface $originalOperation = null): ?ReversibleOperationInterface
    {
        /* @var ProcedureOperation $originalOperation */
        if ($originalOperation && $originalOperation->getName() !== $this->name) {
            throw new InvalidArgumentException('Previous operations must apply to the same procedure.');
        }

        if ($this->getOperation() === ProcedureOperation::CREATE) {
            return new ProcedureOperation(
                $this->getName(),
                ProcedureOperation::DROP
            );
        }

        return $originalOperation;
    }

    /**
     * Returns a new operation by applying another operation.
     *
     * @param ReducibleOperationInterface $operation
     * @return ReducibleOperationInterface|null
     */
    public function apply(ReducibleOperationInterface $operation): ?ReducibleOperationInterface
    {
        /* @var ProcedureOperation $operation */
        if ($operation->getName() !== $this->getName()) {
            throw new InvalidArgumentException('Cannot apply operations for a different procedure.');
        }

        if ($this->getOperation() === self::DROP) {
            throw new InvalidArgumentException('Cannot apply further operations to a dropped procedure.');
        }

        if ($this->getOperation() === self::CREATE) {
            if ($operation->operation === self::CREATE) {
                throw new InvalidArgumentException('Cannot recreate an existing procedure.');
            }

            // Skip creation of procedures that will be dropped
            if ($operation->getOperation() === self::DROP) {
                return null;
            }
        }

        throw new InvalidArgumentException('Only CREATE and DROP operations can be applied to procedures.');
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
     * Returns the deterministic flag.
     *
     * @return bool
     */
    public function getDeterminism(): bool
    {
        return $this->deterministic;
    }

    /**
     * Returns the dataUse characteristic.
     *
     * @return string
     */
    public function getDataUse(): string
    {
        return $this->dataUse;
    }

    /**
     * Returns the language characteristic.
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Returns the IN parameter operations.
     *
     * @return ParameterOperation[]
     */
    public function getInParameterOperations(): array
    {
        return $this->inParameterOperations;
    }

    /**
     * Returns the OUT parameter operations.
     *
     * @return ParameterOperation[]
     */
    public function getOutParameterOperations(): array
    {
        return $this->outParameterOperations;
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
