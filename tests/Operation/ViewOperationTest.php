<?php

namespace Exo\Operation;

class ViewOperationTest extends \PHPUnit\Framework\TestCase
{
    public function testApplyAlterToCreate()
    {
        $base = new ViewOperation('user_view', ViewOperation::CREATE, 'SELECT first_name FROM USERS');

        $operation = $base->apply(new ViewOperation('user_view', ViewOperation::ALTER, 'SELECT first_name, last_name FROM USERS'));

        $this->assertEquals('user_view', $operation->getView());
        $this->assertEquals(ViewOperation::CREATE, $operation->getOperation());
        $this->assertEquals('SELECT first_name, last_name FROM USERS', $operation->getBody());
    }

    public function testApplyDropToCreate()
    {
        $base = new ViewOperation('user_view', ViewOperation::CREATE, 'SELECT first_name FROM USERS');

        $operation = $base->apply(new ViewOperation('user_view', ViewOperation::DROP));

        $this->assertNull($operation);
    }

    public function testApplyAlterToAlter()
    {
        $base = new ViewOperation('user_view', ViewOperation::ALTER, 'SELECT first_name FROM USERS');

        $operation = $base->apply(new ViewOperation('user_view', ViewOperation::ALTER, 'SELECT first_name, last_name FROM USERS'));

        $this->assertEquals('user_view', $operation->getView());
        $this->assertEquals(ViewOperation::ALTER, $operation->getOperation());
        $this->assertEquals('SELECT first_name, last_name FROM USERS', $operation->getBody());
    }

    public function testApplyDropToAlter()
    {
        $base = new ViewOperation('user_view', ViewOperation::ALTER, 'SELECT first_name FROM USERS');

        $drop = new ViewOperation('user_view', ViewOperation::DROP);

        $operation = $base->apply($drop);
        $this->assertEquals($drop->getOperation(), $operation->getOperation());
    }

    public function testReverseCreate()
    {
        $base = new ViewOperation('user_view', ViewOperation::CREATE, 'SELECT first_name FROM USERS');

        $operation = $base->reverse();

        $this->assertEquals('user_view', $operation->getView());
        $this->assertEquals(ViewOperation::DROP, $operation->getOperation());
    }

    public function testReverseAlter()
    {
        $base = new ViewOperation('user_view', ViewOperation::ALTER, 'SELECT first_name, last_name FROM USERS');

        $create = new ViewOperation('user_view', ViewOperation::CREATE, 'SELECT first_name FROM USERS');

        $operation = $base->reverse($create);

        $this->assertEquals('user_view', $operation->getView());
        $this->assertEquals(ViewOperation::ALTER, $operation->getOperation());
        $this->assertEquals('SELECT first_name FROM USERS', $operation->getBody());
    }

    public function testReverseDrop()
    {
        $base = new ViewOperation('user_view', ViewOperation::DROP);

        $create = new ViewOperation('user_view', ViewOperation::CREATE, 'SELECT first_name FROM USERS');

        $operation = $base->reverse($create);

        $this->assertEquals($create, $operation);
    }
}
