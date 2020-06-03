<?php

namespace Exo\Operation;

class FunctionOperation extends AbstractOperation
{
    const CREATE = 'create';
    const ALTER = 'alter';
    const DROP = 'drop';

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
     * FunctionOperation constructor.
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
     * @param FunctionOperation|null $original
     * @return static
     */
    public function reverse(FunctionOperation $original = null): FunctionOperation
    {
        if ($this->getOperation() === FunctionOperation::CREATE) {
            return new FunctionOperation(
                $this->name,
                FunctionOperation::DROP,
                null
            );
        }

        if ($original->getName() !== $this->getName()) {
            throw new \InvalidArgumentException('Previous operations must apply to the same function.');
        }

        // Provide the create function operation to reverse a drop
        if ($this->getOperation() === FunctionOperation::DROP) {
            return $original;
        }

        return new FunctionOperation(
            $this->name,
            FunctionOperation::ALTER,
            $original->getBody()
        );
    }

    /**
     * Returns a new operation by applying another operation.
     *
     * @param FunctionOperation $operation
     * @return FunctionOperation|null
     */
    public function apply(FunctionOperation $operation)
    {
        if ($operation->getName() !== $this->getName()) {
            throw new \InvalidArgumentException('Cannot apply operations for a different function.');
        }

        if ($this->operation === self::DROP) {
            throw new \InvalidArgumentException('Cannot apply further operations to a dropped function.');
        }

        if ($this->operation === self::CREATE) {
            if ($operation->operation === self::CREATE) {
                throw new \InvalidArgumentException('Cannot recreate an existing function.');
            }

            // Skip creation of functions that will be dropped
            if ($operation->operation === self::DROP) {
                return null;
            }
        } else if ($operation->operation === self::DROP) {

            // Skip modification of functions that will be dropped
            return $operation;
        }

        return new FunctionOperation($this->name, $this->operation, $operation->body);
    }

    /**
     * Returns the function name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
