<?php

namespace Exo\Operation;

class ParameterOperation implements OperationInterface
{
    const ADD = 'add';

    /**
     * @var string
     */
    private $parameter;

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
     * @param string $parameter
     * @param string $operation
     * @param array  $options
     */
    public function __construct(string $parameter, string $operation, array $options = [])
    {
        $this->parameter = $parameter;
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
        return $this->parameter;
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
