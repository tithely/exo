<?php

namespace Exo\Operation;

class IndexOperation extends AbstractOperation
{
    const ADD = 'add';
    const DROP = 'DROP';

    /**
     * @var string[]
     */
    private $columns = [];

    /**
     * @var string
     */
    private $operation;

    /**
     * @var array
     */
    private $options = [];

    /**
     * IndexOperation constructor.
     *
     * @param string   $name
     * @param string   $operation
     * @param string[] $columns
     * @param array    $options
     */
    public function __construct(string $name, string $operation, array $columns, array $options)
    {
        $this->name = $name;
        $this->operation = $operation;
        $this->columns = $columns;
        $this->options = $options;
    }

    /**
     * Returns the column names.
     *
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->columns;
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
     * Returns the index options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
