<?php

namespace Exo;

use Exo\Operation\ExecOperation;

final class ExecMigration implements MigrationInterface
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var string|null
     */
    private ?string $body;

    /**
     * Returns a new create exec migration.
     *
     * @param string $name
     * @return static
     */
    public static function create(string $name): ExecMigration
    {
        return new self($name);
    }

    /**
     * Migration constructor.
     *
     * @param string      $name
     * @param string|null $body
     */
    private function __construct(string $name, string $body = null)
    {
        $this->name = $name;
        $this->body = $body;
    }

    /**
     * Pushes a new exec migration.
     *
     * @param string $body
     * @return ExecMigration
     */
    public function withBody(string $body): ExecMigration
    {
        return new ExecMigration($this->name, $body);
    }

    /**
     * Returns the operation of this migration.
     *
     * @return ExecOperation
     */
    public function getOperation(): ExecOperation
    {
        return new ExecOperation($this->name, $this->body);
    }
}
