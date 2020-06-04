<?php

return Exo\ViewMigration::create('user_counts')
    ->withBody('select count(distinct id) as user_count from test.users');
