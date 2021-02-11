<?php

namespace Exo\Operation;

final class ReturnTypeOperation extends AbstractOperation
{
    const ADD = 'add';

    /**
     * @var string
     */
    private string $type;

    /**
     * @var string
     */
    private string $operation;

    /**
     * @var array
     */
    private array $options;

    /**
     * ColumnOperation constructor.
     *
     * @param string $type
     * @param string $operation
     * @param array  $options
     */
    public function __construct(string $type, string $operation, array $options = [])
    {
        $this->name = '';
        $this->type = $type;
        $this->operation = $operation;
        $this->options = $options;
    }

    /**
     * Returns the column name.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
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
