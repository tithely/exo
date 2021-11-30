<?php

return Exo\FunctionMigration::create('user_level')
    ->withParameter('input', ['type' => 'integer'])
    ->withVariable('suffix', ['type' => 'string', 'length' => '20'])
    ->withDeterminism(true)
    ->withReturnType('string', ['type' => 'string', 'length' => 128])
    ->withBody('RETURN CONCAT(CAST(input AS CHAR(25)), suffix);');
