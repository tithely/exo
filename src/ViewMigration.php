<?php

namespace Exo;

use Exo\Operation\ViewOperation;

final class ViewMigration implements MigrationInterface
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
    private ?string $body = null;

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
    private function __construct(string $name, string $operation, ?string $body = null)
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
    public function withBody(string $body): ViewMigration
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
    public function getOperation(): ViewOperation
    {
        return new ViewOperation($this->name, $this->operation, $this->body);
    }
}
