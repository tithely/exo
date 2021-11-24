<?php

return Exo\TableMigration::alter('users')
    ->addColumn('email', ['type' => 'string', 'length' => 255, 'after' => 'id'])
    ->modifyColumn('password', ['type' => 'string', 'length' => 100])
    ->dropColumn('username')
    ->addIndex('users_email_idx', ['password'])
    ->dropIndex('users_id_idx');
