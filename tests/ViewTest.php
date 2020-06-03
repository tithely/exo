<?php

namespace Exo;

use Exo\Operation\ViewOperation;

class ViewTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateView()
    {
        $operation = ViewMigration::create('user_counts')
            ->withBody('SELECT COUNT(username) as usernames FROM USERS')
            ->getOperation();

        $this->assertEquals('user_counts', $operation->getView());
        $this->assertEquals(ViewOperation::CREATE, $operation->getOperation());
        $this->assertEquals('SELECT COUNT(username) as usernames FROM USERS', $operation->getBody());
    }

    public function testAlterView()
    {
        $operation = ViewMigration::alter('user_counts')
            ->withBody('SELECT COUNT(username) as usernames, COUNT(DISTINCT username) as distinct_usernames FROM USERS')
            ->getOperation();

        $this->assertEquals('user_counts', $operation->getView());
        $this->assertEquals(ViewOperation::ALTER, $operation->getOperation());
        $this->assertEquals('SELECT COUNT(username) as usernames, COUNT(DISTINCT username) as distinct_usernames FROM USERS', $operation->getBody());
    }

    public function testDropView()
    {
        $operation = ViewMigration::drop('user_counts')
            ->getOperation();

        $this->assertEquals('user_counts', $operation->getView());
        $this->assertEquals(ViewOperation::DROP, $operation->getOperation());
        $this->assertNull($operation->getBody());
    }

    public function testPreventModifyBodyDuringDrop()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot set view body in a view drop migration.');

        ViewMigration::drop('user_counts')
            ->withBody('SELECT COUNT(username) as usernames FROM USERS');
    }
}
