<?php

namespace Exo;

class InvalidMigrationContextException extends \Exception
{
    public function __construct(string $contextKeyName = '')
    {
        parent::__construct();
        $this->message = sprintf('The current migration requires a context value which was not passed in (%s).', $contextKeyName);
    }
}
