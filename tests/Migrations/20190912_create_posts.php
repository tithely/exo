<?php

return Exo\TableMigration::create('posts')
    ->addColumn('id', ['type' => 'uuid'])
    ->addColumn('user_id', ['type' => 'uuid'])
    ->addColumn('title', ['type' => 'text'])
    ->addColumn('body', ['type' => 'text']);
