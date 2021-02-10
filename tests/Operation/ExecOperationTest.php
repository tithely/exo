<?php

namespace Exo\Operation;

use Exo\ViewMigration;

class ExecOperationTest extends \PHPUnit\Framework\TestCase
{
    public function testApply()
    {
        $sql = 'INSERT INTO USERS (id, first_name, last_name) VALUES (\'1\', \'Bob\',\'Smith\')';
        $base = new ExecOperation('user_seed', ExecOperation::EXEC, $sql);
        $operation = $base->apply(new ExecOperation('user_seed', ExecOperation::EXEC, $sql));
        $this->assertEquals('user_seed', $operation->getName());
        $this->assertEquals(ExecOperation::EXEC, $operation->getOperation());
        $this->assertEquals($sql, $operation->getBody());
    }

    public function testErrorOnBadOperation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid/Incompatible Operation Type Specified.');

        $sql = 'INSERT INTO USERS (id, first_name, last_name) VALUES (\'1\', \'Bob\',\'Smith\')';
        $base = new ExecOperation('original_name', ViewOperation::CREATE, $sql);
        $base->apply(new ExecOperation('original_name', ExecOperation::EXEC, $sql));
    }

    public function testErrorOnApplyAlterToExecName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot apply operations for a different execution.');

        $sql = 'INSERT INTO USERS (id, first_name, last_name) VALUES (\'1\', \'Bob\',\'Smith\')';
        $base = new ExecOperation('original_name', ExecOperation::EXEC, $sql);
        $base->apply(new ExecOperation('new_name', ExecOperation::EXEC, $sql));
    }
}
