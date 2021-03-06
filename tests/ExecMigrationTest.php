<?php

namespace Exo;

use PHPUnit\Framework\TestCase;

class ExecMigrationTest extends TestCase
{
    public function testExecMigration()
    {
        $sql = 'INSERT INTO USERS (email, password) VALUES (\'bob@smith.com\',\'some_password!\')';
        $migration = ExecMigration::create('insert_user_script')
            ->withBody($sql);

        $operation = $migration->getOperation();

        $this->assertEquals('insert_user_script', $operation->getName());
        $this->assertEquals('execute', $operation->getOperation());
        $this->assertEquals($sql, $operation->getBody());
    }
}
