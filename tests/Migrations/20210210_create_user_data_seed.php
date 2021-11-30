<?php

return Exo\ExecMigration::create('user_data_seed')
    ->withBody("INSERT INTO users (id, email, password) VALUES (1, 'bob@smith.com','some_password!')");
