<?php

namespace Exo\Operation;

final class ExecOperation extends AbstractOperation
{

    /**
     * @var string|null
     */
    private ?string $body;

    /**
     * ExecOperation constructor.
     *
     * @param string   $name
     * @param ?string   $body
     */
    public function __construct(string $name, string $body = null) {
        $this->name = $name;
        $this->body = $body;
    }

    /**
     * Returns the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the operation.
     *
     * @return string
     */
    public function getOperation(): string
    {
        return 'execute';
    }

    /**
     * Returns the body.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }
}
