<?php

return Exo\TableMigration::create('users')
    ->addColumn('username', ['type' => 'string']);
