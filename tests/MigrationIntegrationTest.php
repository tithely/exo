<?php

namespace Exo\Tests;

use Exo\Handler;
use Exo\Statement\MysqlStatementBuilder;
use PDO;
use Exo\Operation\ColumnOperation;
use Exo\Operation\TableOperation;
use Exo\Tests\Traits\UsesYamlConfig;
use Exo\Util\Finder;
use PHPUnit\Framework\TestCase;

class MigrationIntegrationTest extends TestCase
{
    use UsesYamlConfig;

    /**
     * @var PDO|null
     */
    private ?PDO $mysql;

    public function setUp(): void
    {
        if ($mysql = self::yaml('handlers.mysql')) {
            try {
                $this->mysql = new PDO(
                    sprintf('mysql:dbname=%s;host=%s;port=%s', $mysql['name'], $mysql['host'], $mysql['port']),
                    $mysql['user'],
                    $mysql['pass']
                );

                $this->mysql->exec('DROP TABLE IF EXISTS users;');
            } catch (\PDOException $e) {
                $this->markTestSkipped('No MySQL connection');
            }
        }
    }

    public function tearDown(): void
    {
        $this->mysql = null;
    }

    public function testReduceMigrationsWithChange()
    {
        $finder = new Finder([]);
        $history = $finder->fromPath(__DIR__ . '/Fixtures/TestChange');
        $operations = $history->play('20211015_create_users', '20211018_change_username_to_user_id', true);

        $this->assertCount(1, $operations);

        $operation = $operations[0];

        $this->assertSame('users', $operation->getName());
        $this->assertSame(TableOperation::CREATE, $operation->getOperation());
        $this->assertCount(2, $operation->getColumnOperations());

        list($operation1, $operation2) = $operation->getColumnOperations();

        $this->assertSame('email', $operation1->getName());
        $this->assertSame(ColumnOperation::ADD, $operation1->getOperation());
        $this->assertSame('string', $operation1->getOptions()['type']);

        $this->assertSame('user_id', $operation2->getName());
        $this->assertSame(ColumnOperation::ADD, $operation2->getOperation());
        $this->assertSame('integer', $operation2->getOptions()['type']);
    }

    public function testRewindMigrationsWithChange()
    {
        $finder = new Finder([]);
        $history = $finder->fromPath(__DIR__ . '/Fixtures/TestChange');
        $operations = $history->rewind('20211018_change_username_to_user_id', '20211017_add_column_username', false);
        $this->assertCount(2, $operations);

        list($operation1, $operation2) = $operations;

        $this->assertSame('users', $operation1->getName());
        $this->assertSame(TableOperation::ALTER, $operation1->getOperation());
        $this->assertCount(1, $operation1->getColumnOperations());
        $this->assertSame('user_id', $operation1->getColumnOperations()[0]->getName());
        $this->assertSame(ColumnOperation::CHANGE, $operation1->getColumnOperations()[0]->getOperation());
        $this->assertSame('username', $operation1->getColumnOperations()[0]->getOptions()['new_name']);

        $this->assertSame('users', $operation2->getName());
        $this->assertSame(TableOperation::ALTER, $operation2->getOperation());
        $this->assertCount(1, $operation2->getColumnOperations());
        $this->assertSame('username', $operation2->getColumnOperations()[0]->getName());
        $this->assertSame(ColumnOperation::DROP, $operation2->getColumnOperations()[0]->getOperation());
    }

    public function testReduceRewindMigrationsWithChange()
    {
        $finder = new Finder([]);
        $history = $finder->fromPath(__DIR__ . '/Fixtures/TestChange');
        $operations = $history->rewind('20211018_change_username_to_user_id', '20211017_add_column_username', true);
        $this->assertCount(1, $operations);

        $operation = $operations[0];

        $this->assertSame('users', $operation->getName());
        $this->assertSame(TableOperation::ALTER, $operation->getOperation());
        $this->assertCount(1, $operation->getColumnOperations());

        list($operation1) = $operation->getColumnOperations();
        $this->assertSame('user_id', $operation1->getName());
        $this->assertSame(ColumnOperation::DROP, $operation1->getOperation());
    }

    public function testMigrateMigrationsWithMysql()
    {
        if (!$this->mysql) {
            $this->markTestSkipped('No MySQL connection');
        }

        $finder = new Finder([]);
        $history = $finder->fromPath(__DIR__ . '/Fixtures/TestChange');
        $handler = new Handler($this->mysql, $history);

        $handler->migrate([], null, true);

        $usersTable = $this->mysql->query('DESCRIBE users')->fetchAll();

        $this->assertSame('email', $usersTable[0]['Field']);
        $this->assertStringContainsString('varchar', $usersTable[0]['Type']);
        $this->assertSame('user_id', $usersTable[1]['Field']);
        $this->assertStringContainsString('int', $usersTable[1]['Type']);
    }

    public function testRollbackMigrationsWithMysql()
    {
        if (!$this->mysql) {
            $this->markTestSkipped('No MySQL connection');
        }

        $finder = new Finder([]);
        $history = $finder->fromPath(__DIR__ . '/Fixtures/TestChange');
        $handler = new Handler($this->mysql, $history);
        $handler->migrate([], null, true);

        $handler->rollback(
            [
                '20211015_create_users',
                '20211016_change_username_to_email',
                '20211017_add_column_username',
                '20211018_change_username_to_user_id'
            ],
            '20211017_add_column_username',
            true
        );

        $usersTable = $this->mysql->query('DESCRIBE users')->fetchAll();

        $this->assertSame('email', $usersTable[0]['Field']);
        $this->assertStringContainsString('varchar', $usersTable[0]['Type']);
        $this->assertSame('username', $usersTable[1]['Field']);
        $this->assertStringContainsString('varchar', $usersTable[1]['Type']);
    }
}
