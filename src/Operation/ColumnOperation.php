<?php

namespace Exo\Operation;

class ColumnOperation implements OperationInterface
{
    const ADD = 'add';
    const MODIFY = 'modify';
    const DROP = 'drop';

    /**
     * @var string
     */
    private $column;

    /**
     * @var string
     */
    private $operation;

    /**
     * @var array
     */
    private $options = [];

    /**
     * ColumnOperation constructor.
     *
     * @param string $column
     * @param string $operation
     * @param array  $options
     */
    public function __construct(string $column, string $operation, array $options)
    {
        $this->column = $column;
        $this->operation = $operation;
        $this->options = $options;
    }

    /**
     * Returns the column name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->column;
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
     * Returns the column options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
