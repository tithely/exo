<?php

namespace Exo\Operation;

final class ColumnOperation extends AbstractOperation
{
    const ADD = 'add';
    const MODIFY = 'modify';
    const DROP = 'drop';

    /**
     * @var string
     */
    private string $operation;

    /**
     * @var array
     */
    private array $options = [];

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
