<?php

namespace Exo;

use Exo\Operation\ViewOperation;

class ViewMigrationTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateView()
    {
        $operation = ViewMigration::create('user_counts')
            ->withBody('SELECT COUNT(username) as usernames FROM USERS')
            ->getOperation();

        $this->assertEquals('user_counts', $operation->getName());
        $this->assertEquals(ViewOperation::CREATE, $operation->getOperation());
        $this->assertEquals('SELECT COUNT(username) as usernames FROM USERS', $operation->getBody());
    }

    public function testCreateViewWithContextHappy()
    {
        $templateBody = "SELECT COUNT(username) as usernames FROM {{tenant_database_name}}.USERS";

        $expectedContext = [
            'catalog_database_name',
            'tenant_database_name'
        ];

        $actualContext = [
            'catalog_database_name' => 'catalog_db',
            'tenant_database_name' => 'tenant_123'
        ];

        $migration = ViewMigration::create('user_counts')
            ->withBody($templateBody)
            ->withExpectedContext($expectedContext);

        $this->assertEquals($templateBody, $migration->getBody());
        $this->assertEquals($expectedContext, $migration->getExpectedContext());

        $operation = $migration
            ->getOperation($actualContext);

        $this->assertEquals(
            $actualContext,
            $migration->getContext()
        );

        $this->assertEquals(
            'SELECT COUNT(username) as usernames FROM tenant_123.USERS',
            $operation->getBody()
        );
    }

    public function testCreateViewWithContextSad()
    {
        $templateBody = "
            SELECT COUNT(username) as usernames FROM {{catalog_database_name}}.USERS
            UNION
            SELECT COUNT(username) as usernames FROM {{tenant_database_name}}.USERS
        ";

        $expectedContext = [
            'catalog_database_name',
            'tenant_database_name'
        ];

        $actualContext = [
            'catalog_database_name' => 'catalog_db'
        ];

        $migration = ViewMigration::create('user_counts')
            ->withBody($templateBody)
            ->withExpectedContext($expectedContext);
        $this->assertEquals($templateBody, $migration->getBody());

        $this->expectException(InvalidMigrationContextException::class);
        $this->expectExceptionMessage('The current migration requires a context value which was not passed in (tenant_database_name).');

        $migration->getOperation($actualContext);
    }

    public function testAlterView()
    {
        $operation = ViewMigration::alter('user_counts')
            ->withBody('SELECT COUNT(username) as usernames, COUNT(DISTINCT username) as distinct_usernames FROM USERS')
            ->getOperation();

        $this->assertEquals('user_counts', $operation->getName());
        $this->assertEquals(ViewOperation::ALTER, $operation->getOperation());
        $this->assertEquals('SELECT COUNT(username) as usernames, COUNT(DISTINCT username) as distinct_usernames FROM USERS', $operation->getBody());
    }

    public function testDropView()
    {
        $operation = ViewMigration::drop('user_counts')
            ->getOperation();

        $this->assertEquals('user_counts', $operation->getName());
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
