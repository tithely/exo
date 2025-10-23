<?php

namespace Exo\Operation;

final class ColumnOperation extends AbstractOperation
{
    const ADD = 'add';
    const MODIFY = 'modify';
    const CHANGE = 'change';
    const DROP = 'drop';

    /**
     * @var string
     */
    private string $operation;

    /**
     * @var string
     */
    private string $beforeName;

    /**
     * @var string
     */
    private string $afterName;

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
        
        if ($operation === self::CHANGE && isset($options['new_name'])) {
            $this->beforeName = $name;
            $this->afterName = $options['new_name'];
        } else {
            $this->beforeName = $name;
            $this->afterName = $name;
        }
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

    /**
     * Returns the before name (original name for CHANGE operations).
     *
     * @return string
     */
    public function getBeforeName(): string
    {
        return $this->beforeName;
    }

    /**
     * Returns the after name (new name for CHANGE operations).
     *
     * @return string
     */
    public function getAfterName(): string
    {
        return $this->afterName;
    }
}
