<?php

namespace Exo\Operation;

class VariableOperation extends AbstractOperation
{
    const ADD = 'add';

    /**
     * @var string
     */
    private $variable;

    /**
     * @var string
     */
    private $operation;

    /**
     * @var array
     */
    private $options;

    /**
     * ColumnOperation constructor.
     *
     * @param string $variable
     * @param string $operation
     * @param array  $options
     */
    public function __construct(string $variable, string $operation, array $options = [])
    {
        $this->variable = $variable;
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
        return $this->variable;
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
