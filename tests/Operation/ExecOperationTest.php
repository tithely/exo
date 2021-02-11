<?php

namespace Exo\Operation;

class ExecOperationTest extends \PHPUnit\Framework\TestCase
{
    public function testApply()
    {
        $sql = 'INSERT INTO USERS (id, first_name, last_name) VALUES (\'1\', \'Bob\',\'Smith\')';
        $base = new ExecOperation('user_seed', $sql);
        $operation = $base->apply(new ExecOperation('user_seed', $sql));
        $this->assertEquals('user_seed', $operation->getName());
        $this->assertEquals('execute', $operation->getOperation());
        $this->assertEquals($sql, $operation->getBody());
    }

    public function testErrorOnApplyAlterToExecName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot apply operations for a different execution.');

        $sql = 'INSERT INTO USERS (id, first_name, last_name) VALUES (\'1\', \'Bob\',\'Smith\')';
        $base = new ExecOperation('original_name', $sql);
        $base->apply(new ExecOperation('new_name', $sql));
    }
}
