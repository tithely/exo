<?php

namespace Exo\Operation;

use InvalidArgumentException;

final class ViewOperation extends AbstractOperation implements ReversibleOperationInterface, ReducingOperationInterface
{
    const CREATE = 'create';
    const ALTER = 'alter';
    const DROP = 'drop';

    /**
     * @var string
     */
    private string $operation;

    /**
     * @var string|null
     */
    private ?string $body;

    /**
     * ViewOperation constructor.
     *
     * @param string      $name
     * @param string      $operation
     * @param string|null $body
     */
    public function __construct(string $name, string $operation, string $body = null)
    {
        $this->name = $name;
        $this->operation = $operation;
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
        /* @var ViewOperation $originalOperation */
        if ($this->getOperation() === ViewOperation::CREATE) {
            return new ViewOperation(
                $this->getName(),
                ViewOperation::DROP,
                null
            );
        }

        if ($originalOperation && $originalOperation->getName() !== $this->name) {
            throw new InvalidArgumentException('Previous operations must apply to the same view.');
        }

        // Provide the create view operation to reverse a drop
        if ($this->getOperation() === ViewOperation::DROP) {
            return $originalOperation;
        }

        return new ViewOperation(
            $this->getName(),
            ViewOperation::ALTER,
            $originalOperation->getBody()
        );
    }

    /**
     * Returns a new operation by applying another operation.
     *
     * @param ReducingOperationInterface $operation
     * @return ReducingOperationInterface|null
     */
    public function apply(ReducingOperationInterface $operation): ?ReducingOperationInterface
    {
        /* @var ViewOperation $operation */
        if ($operation->getName() !== $this->getName()) {
            throw new InvalidArgumentException('Cannot apply operations for a different view.');
        }

        if ($this->getOperation() === self::DROP) {
            throw new InvalidArgumentException('Cannot apply further operations to a dropped view.');
        }

        if ($this->getOperation() === self::CREATE) {
            if ($operation->operation === self::CREATE) {
                throw new InvalidArgumentException('Cannot recreate an existing view.');
            }

            // Skip creation of views that will be dropped
            if ($operation->getOperation() === self::DROP) {
                return null;
            }
        } else if ($operation->getOperation() === self::DROP) {

            // Skip modification of views that will be dropped
            return $operation;
        }

        return new ViewOperation(
            $this->getName(),
            $this->getOperation(),
            $operation->getBody()
        );
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
     * Returns the SQL body.
     *
     * @return string|null
     */
    public function getBody(): ?string
    {
        return $this->body;
    }
}
