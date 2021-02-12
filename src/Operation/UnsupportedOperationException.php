<?php

namespace Exo\Operation;

use Exception;

class UnsupportedOperationException extends Exception
{
    public function __construct(string $className = '')
    {
        parent::__construct();
        $this->message = sprintf('The operation type passed in (%s) is not supported', $className);
    }
}
