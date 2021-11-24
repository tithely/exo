<?php

return Exo\ViewMigration::create('user_counts')
    ->withBody('SELECT COUNT(id) AS user_count FROM users');
