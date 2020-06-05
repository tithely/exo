<?php

namespace Exo\Operation;

class ReturnTypeOperation implements OperationInterface
{
    const ADD = 'add';

    /**
     * @var string
     */
    private $type;

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
     * @param string $type
     * @param string $operation
     * @param array  $options
     */
    public function __construct(string $type, string $operation, array $options = [])
    {
        $this->type = $type;
        $this->operation = $operation;
        $this->options = $options;
    }

    /**
     * Returns the operation name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'return-type';
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
