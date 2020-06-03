<?php

namespace Exo;

class FunctionMigration
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
    private $body = null;

    /**
     * Returns a new create view migration.
     *
     * @param string $name
     * @return static
     */
    public static function create(string $name)
    {
        return new self($name, FunctionMigration::CREATE);
    }

    /**
     * Returns a new alter view migration.
     *
     * @param string $name
     * @return static
     */
    public static function alter(string $name)
    {
        return new self($name, FunctionMigration::ALTER);
    }

    /**
     * Returns a new drop view migration.
     *
     * @param string $name
     * @return static
     */
    public static function drop(string $name)
    {
        return new self($name, FunctionMigration::DROP);
    }

    /**
     * Migration constructor.
     *
     * @param string      $name
     * @param string      $operation
     * @param string|null $body
     */
    private function __construct(string $name, string $operation, string $body = null)
    {
        $this->name = $name;
        $this->operation = $operation;
        $this->body = $body;
    }

    /**
     * Pushes a new add column operation.
     *
     * @param string $body
     * @return FunctionMigration
     */
    public function withBody(string $body): self
    {
        if ($this->operation === FunctionMigration::DROP) {
            throw new \LogicException('Cannot set view body in a view drop migration.');
        }

        return new FunctionMigration($this->name, $this->operation, $body);
    }

    /**
     * Returns the table operation.
     *
     * @return FunctionMigration
     */
    public function getOperation()
    {
        return new FunctionMigration($this->name, $this->operation, $this->body);
    }
}
