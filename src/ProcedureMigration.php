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
    private string $dataUse;

    /**
     * @var string
     */
    private string $language;

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
     * @param string      $dataUse
     * @param string      $language
     * @param array       $inParameterOperations
     * @param array       $outParameterOperations
     * @param string|null $body
     */
    private function __construct(
        string $name,
        string $operation,
        bool $deterministic = false,
        string $dataUse = 'READS SQL DATA',
        string $language = 'plpgsql',
        array $inParameterOperations = [],
        array $outParameterOperations = [],
        ?string $body = null
    ) {
        $this->name = $name;
        $this->operation = $operation;
        $this->deterministic = $deterministic;
        $this->dataUse = $dataUse;
        $this->language = $language;
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
            $this->dataUse,
            $this->language,
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
    public function withDeterminism(bool $deterministic): ProcedureMigration
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
     * @param string $dataUse
     * @return $this
     */
    public function withDataUse(string $dataUse): ProcedureMigration
    {
        if ($this->operation === ProcedureOperation::DROP) {
            throw new LogicException('Cannot set dataUse property in a procedure drop migration.');
        }

        if (!in_array($dataUse, ['CONTAINS SQL', 'NO SQL', 'READS SQL DATA', 'MODIFIES SQL DATA'])) {
            throw new LogicException('Cannot set dataUse, not a valid option.');
        }

        $this->dataUse = $dataUse;

        return $this;
    }

    /**
     * Sets the language property of the procedure.
     *
     * @param string $language
     * @return $this
     */
    public function withLanguage(string $language): ProcedureMigration
    {
        if ($this->operation === ProcedureOperation::DROP) {
            throw new LogicException('Cannot set language property in a procedure drop migration.');
        }

        if (!in_array($language, ['SQL', 'plpgsql'])) {
            throw new LogicException('Cannot set language, not a valid option.');
        }

        $this->language = $language;

        return $this;
    }

    /**
     * Pushes a new IN add parameter operation.
     *
     * @param string $name
     * @param array  $options
     * @return $this
     */
    public function withInParameter(string $name, array $options = []): ProcedureMigration
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
    public function withOutParameter(string $name, array $options = []): ProcedureMigration
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
