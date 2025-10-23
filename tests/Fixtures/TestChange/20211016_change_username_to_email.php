<?php

return Exo\TableMigration::alter('users')
    ->changeColumn('username', 'email', ['type' => 'string']);
