<?php

namespace Exo\Operation;

class IndexOperation extends AbstractOperation
{
    const ADD_OPERATION = 'add';
    const DROP_OPERATION = 'DROP';

    /**
     * @var string
     */
    private $name;

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
     * Returns the reverse of the operation.
     *
     * @return static
     */
    public function reverse()
    {
        return $this;
    }

    /**
     * Returns the index name.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
