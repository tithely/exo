<?php

return Exo\TableMigration::alter('users')
    ->changeColumn('username', 'user_id', ['type' => 'integer']);
