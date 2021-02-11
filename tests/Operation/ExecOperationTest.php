<?php

namespace Exo\Operation;

class ExecOperationTest extends \PHPUnit\Framework\TestCase
{
    public function testApply()
    {
        $sql = 'INSERT INTO USERS (id, first_name, last_name) VALUES (\'1\', \'Bob\',\'Smith\')';
        $operation = new ExecOperation('user_seed', $sql);
        $this->assertEquals('user_seed', $operation->getName());
        $this->assertEquals('execute', $operation->getOperation());
        $this->assertEquals($sql, $operation->getBody());
    }
}
