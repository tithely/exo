<?php

namespace Exo;

use Exo\Operation\ExecOperation;

final class ExecMigration implements MigrationInterface
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
    public static function create(string $name): ExecMigration
    {
        return new self($name, ExecOperation::EXEC);
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
     * Pushes a new exec operation.
     *
     * @param string $body
     * @return ExecMigration
     */
    public function withBody(string $body): ExecMigration
    {
        return new ExecMigration($this->name, $this->operation, $body);
    }

    /**
     * Returns the table operation.
     *
     * @return ExecOperation
     */
    public function getOperation(): ExecOperation
    {
        return new ExecOperation($this->name, $this->operation, $this->body);
    }
}
