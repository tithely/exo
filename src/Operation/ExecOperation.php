<?php

namespace Exo\Operation;

use InvalidArgumentException;

class ExecOperation extends AbstractOperation
{

    /**
     * @var string|null
     */
    private $body;

    /**
     * ExecOperation constructor.
     *
     * @param string   $name
     * @param ?string   $body
     */
    public function __construct(string $name, string $body = null) {
        $this->name = $name;
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
        return 'execute';
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

        return new ExecOperation(
            $this->getName(),
            $operation->getBody()
        );
    }

}
