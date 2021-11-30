<?php

return Exo\TableMigration::alter('users')
    ->addColumn('email', ['type' => 'string', 'unique' => true, 'length' => 255, 'after' => 'id'])
    ->modifyColumn('password', ['type' => 'string', 'length' => 100])
    ->dropColumn('username')
    ->dropIndex('users');
