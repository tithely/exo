<?php

namespace Exo;

use Exo\Operation\ParameterOperation;
use Exo\Operation\ProcedureOperation;
use LogicException;

final class ProcedureMigration implements MigrationInterface
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
     * @var bool
     */
    private bool $deterministic;

    /**
     * @var string
     */
    private string $readsSqlData;

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
    private ?string $body = null;

    /**
     * Returns a new create procedure migration.
     *
     * @param string $name
     * @return static
     */
    public static function create(string $name)
    {
        return new self($name, ProcedureOperation::CREATE);
    }

    /**
     * Returns a new drop procedure migration.
     *
     * @param string $name
     * @return static
     */
    public static function drop(string $name)
    {
        return new self($name, ProcedureOperation::DROP);
    }

    /**
     * Migration constructor.
     *
     * @param string      $name
     * @param string      $operation
     * @param bool        $deterministic
     * @param string      $readsSqlData
     * @param array       $inParameterOperations
     * @param array       $outParameterOperations
     * @param string|null $body
     */
    private function __construct(
        string $name,
        string $operation,
        bool $deterministic = false,
        string $readsSqlData = 'READS SQL DATA',
        array $inParameterOperations = [],
        array $outParameterOperations = [],
        string $body = null
    ) {
        $this->name = $name;
        $this->operation = $operation;
        $this->deterministic = $deterministic;
        $this->readsSqlData = $readsSqlData;
        $this->inParameterOperations = $inParameterOperations;
        $this->outParameterOperations = $outParameterOperations;
        $this->body = $body;
    }

    /**
     * Pushes a new add column operation.
     *
     * @param string $body
     * @return ProcedureMigration
     */
    public function withBody(string $body): ProcedureMigration
    {
        if ($this->operation !== ProcedureOperation::CREATE) {
            throw new LogicException('Procedure body can only be set in a procedure create migration.');
        }

        $this->body = $body;

        return $this;
    }

    /**
     * Returns the table operation.
     *
     * @return ProcedureOperation
     */
    public function getOperation(): ProcedureOperation
    {
        return new ProcedureOperation(
            $this->name,
            $this->operation,
            $this->deterministic,
            $this->readsSqlData,
            $this->inParameterOperations,
            $this->outParameterOperations,
            $this->body
        );
    }

    /**
     * Sets determinism for the function.
     *
     * @param bool $deterministic
     * @return $this
     */
    public function isDeterministic(bool $deterministic): ProcedureMigration
    {
        if ($this->operation === ProcedureOperation::DROP) {
            throw new LogicException('Cannot set deterministic property in a procedure drop migration.');
        }

        $this->deterministic = $deterministic;

        return $this;
    }

    /**
     * Sets the reads sql data property of the function.
     *
     * @param string $readsSqlData
     * @return $this
     */
    public function readsSqlData(string $readsSqlData): ProcedureMigration
    {
        if ($this->operation === ProcedureOperation::DROP) {
            throw new LogicException('Cannot set readsSqlData property in a procedure drop migration.');
        }

        if (!in_array($readsSqlData, ['CONTAINS SQL', 'NO SQL', 'READS SQL DATA', 'MODIFIES SQL DATA'])) {
            throw new LogicException('Cannot set readsSqlData, not a valid option.');
        }

        $this->readsSqlData = $readsSqlData;

        return $this;
    }

    /**
     * Pushes a new IN add parameter operation.
     *
     * @param string $name
     * @param array  $options
     * @return $this
     */
    public function addInParameter(string $name, array $options = []): ProcedureMigration
    {
        if ($this->operation === ProcedureOperation::DROP) {
            throw new LogicException('Cannot add parameters in a procedure drop migration.');
        }

        array_push(
            $this->inParameterOperations,
            new ParameterOperation($name, ParameterOperation::ADD, $options)
        );

        return $this;
    }

    /**
     * Pushes a new OUT add parameter operation.
     *
     * @param string $name
     * @param array  $options
     * @return $this
     */
    public function addOutParameter(string $name, array $options = []): ProcedureMigration
    {
        if ($this->operation === ProcedureOperation::DROP) {
            throw new LogicException('Cannot add parameters in a procedure drop migration.');
        }

        array_push(
            $this->outParameterOperations,
            new ParameterOperation($name, ParameterOperation::ADD, $options)
        );

        return $this;
    }
}
