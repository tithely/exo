<?php

namespace Exo\Operation;

class ColumnOperation extends AbstractOperation
{
    const ADD = 'add';
    const MODIFY = 'modify';
    const DROP = 'drop';

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
     * @param string $name
     * @param string $operation
     * @param array  $options
     */
    public function __construct(string $name, string $operation, array $options)
    {
        $this->name = $name;
        $this->operation = $operation;
        $this->options = $options;
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
