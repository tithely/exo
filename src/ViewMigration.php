<?php

namespace Exo;

use Exo\Operation\ColumnOperation;
use Exo\Operation\IndexOperation;
use Exo\Operation\TableOperation;
use Exo\Operation\ViewOperation;

class ViewMigration
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
        return new self($name, ViewOperation::CREATE);
    }

    /**
     * Returns a new alter view migration.
     *
     * @param string $name
     * @return static
     */
    public static function alter(string $name)
    {
        return new self($name, ViewOperation::ALTER);
    }

    /**
     * Returns a new drop view migration.
     *
     * @param string $name
     * @return static
     */
    public static function drop(string $name)
    {
        return new self($name, ViewOperation::DROP);
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
     * @return ViewMigration
     */
    public function withBody(string $body): self
    {
        if ($this->operation === ViewOperation::DROP) {
            throw new \LogicException('Cannot set view body in a view drop migration.');
        }

        return new ViewMigration($this->name, $this->operation, $body);
    }

    /**
     * Returns the table operation.
     *
     * @return ViewOperation
     */
    public function getOperation()
    {
        return new ViewOperation($this->name, $this->operation, $this->body);
    }
}
