<?php

return Exo\Migration::alter('users')
    ->addColumn('email', ['type' => 'string', 'length' => 255, 'after' => 'id'])
    ->dropColumn('username');
