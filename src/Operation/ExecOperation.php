<?php

namespace Exo\Operation;

use InvalidArgumentException;

class ExecOperation extends AbstractOperation
{
    const EXEC = 'execute';

    /**
     * @var string
     */
    private $operation;

    /**
     * @var string|null
     */
    private $body;

    /**
     * IndexOperation constructor.
     *
     * @param string   $name
     * @param string   $operation
     * @param ?string   $body
     */
    public function __construct(string $name, string $operation, string $body = null) {
        $this->name = $name;
        $this->operation = $operation;
        $this->body = $body;
    }

    /**
     * Returns the name.
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
     * Returns the body.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Returns a new operation by applying another operation.
     *
     * @param ExecOperation $operation
     * @return ExecOperation|null
     */
    public function apply(ExecOperation $operation)
    {
        if ($operation->getName() !== $this->getName()) {
            throw new InvalidArgumentException('Cannot apply operations for a different execution.');
        }

        if ($this->getOperation() !== self::EXEC) {
            throw new InvalidArgumentException('Invalid/Incompatible Operation Type Specified.');
        }

        return new ExecOperation(
            $this->getName(),
            $this->getOperation(),
            $operation->getBody()
        );
    }

}
