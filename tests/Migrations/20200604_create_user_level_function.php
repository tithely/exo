<?php

return Exo\FunctionMigration::create('user_level')
    ->withReturnType('integer')
    ->addParameter('inputValue', ['type' => 'integer'])
    ->isDeterministic(false)
    ->addVariable('suffixValue', ['length' => '20'])
    ->withBody('RETURN CONCAT(CAST(inputValue AS CHAR(25), suffixValue);');
