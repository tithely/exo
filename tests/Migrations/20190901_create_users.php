<?php

return Exo\Migration::create('users')
    ->addColumn('id', ['type' => 'uuid'])
    ->addColumn('username', ['type' => 'string', 'length' => 64])
    ->addColumn('password', ['type' => 'string', 'length' => 255]);
