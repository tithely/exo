<?php

return Exo\TableMigration::alter('users')
    ->addColumn('username', ['type' => 'string']);
