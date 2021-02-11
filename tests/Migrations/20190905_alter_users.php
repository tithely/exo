<?php

return Exo\TableMigration::alter('users')
    ->addColumn('email', ['type' => 'string', 'length' => 255, 'after' => 'id'])
    ->dropColumn('username');
