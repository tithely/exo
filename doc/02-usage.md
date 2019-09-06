# Usage

## Basic Example

```php
<?php

use Exo\Handler;
use Exo\History;
use Exo\Migration;

$history = new History();

$history->add('1', Migration::create('users')
    ->addColumn('id', ['type' => 'uuid', 'primary' => true])
    ->addColumn('username', ['type' => 'string', 'unique' => true])
    ->addColumn('password', ['type' => 'string']);
);

$history->add('2', Migration::alter('users')
    ->addColumn('email', ['type' => 'string', 'unique' => true, 'after' => 'username'])
);

$pdo = new PDO('mysql:dbname=foo;host=bar', 'user', 'pass');
$handler = new Handler($pdo, $history);

// Migrate from version '1' to '2'
$handler->migrate('1', '2', true);
```
