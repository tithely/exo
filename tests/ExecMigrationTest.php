<?php

namespace Exo;

use Exo\Operation\ExecOperation;

class ExecMigrationTest extends \PHPUnit\Framework\TestCase
{
    public function testExecMigration()
    {
        $sql = 'INSERT INTO USERS (email, password) VALUES (\'bob@smith.com\',\'some_password!\')';
        $migration = ExecMigration::create('insert_user_script')
            ->withBody($sql);

        $operation = $migration->getOperation();

        $this->assertEquals('insert_user_script', $operation->getName());
        $this->assertEquals(ExecOperation::EXEC, $operation->getOperation());
        $this->assertEquals($sql, $operation->getBody());
    }
}
