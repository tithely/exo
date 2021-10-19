<?php

namespace Exo;

use Exo\Tests\Traits\UsesYamlConfig;
use PDO;
use PHPUnit\Framework\TestCase;

class MysqlHandlerTest extends TestCase
{
    use UsesYamlConfig;

    /**
     * @var PDO|null
     */
    private ?PDO $pdo;

    public function setUp(): void
    {
        $mysql = self::yaml('handlers.mysql');

        $this->pdo = new PDO(
            sprintf('mysql:dbname=%s;host=%s;port=%s', $mysql['name'], $mysql['host'], $mysql['port']),
            $mysql['user'],
            $mysql['pass']
        );
        $this->pdo->exec('DROP TABLE IF EXISTS users;');
        $this->pdo->exec('DROP TABLE IF EXISTS users_sessions;');
        $this->pdo->exec('DROP VIEW IF EXISTS user_counts;');
        $this->pdo->exec('DROP FUNCTION IF EXISTS user_level;');
        $this->pdo->exec('DROP FUNCTION IF EXISTS user_count_function;');
        $this->pdo->exec('DROP PROCEDURE IF EXISTS post_count;');
    }

    public function tearDown(): void
    {
        $this->pdo = null;
    }

    public function testFullMigration()
    {
        $handler = $this->getHandler();
        $results = $handler->migrate([], null, false);

        $this->assertCount(9, $results);
        $this->assertTrue($results[0]->isSuccess());
        $this->assertEquals('1', $results[0]->getVersion());
        $this->assertTrue($results[1]->isSuccess());
        $this->assertEquals('2', $results[1]->getVersion());
        $this->assertTrue($results[2]->isSuccess());
        $this->assertEquals('3', $results[2]->getVersion());
        $this->assertTrue($results[3]->isSuccess());
        $this->assertEquals('4', $results[3]->getVersion());
        $this->assertTrue($results[4]->isSuccess());
        $this->assertEquals('5', $results[4]->getVersion());
        $this->assertTrue($results[5]->isSuccess());
        $this->assertEquals('6', $results[5]->getVersion());
        $this->assertTrue($results[6]->isSuccess());
        $this->assertEquals('7', $results[6]->getVersion());
        $this->assertTrue($results[7]->isSuccess());
        $this->assertEquals('8', $results[7]->getVersion());
        $this->assertTrue($results[8]->isSuccess());
        $this->assertEquals('9', $results[8]->getVersion());

        // Verify Exec Results
        $seedCheck = $this->pdo->query('select * from users')->fetchAll();
        $this->assertEquals('7', $results[6]->getVersion());

        // 1 Row Expected
        $this->assertCount(1, $seedCheck);

        // Get First Row
        $seedRow = $seedCheck[0];

        // Validate Data
        $this->assertEquals('1', $seedRow[0]);
        $this->assertEquals('1', $seedRow['id']);
        $this->assertEquals('bob@smith.com', $seedRow[1]);
        $this->assertEquals('bob@smith.com', $seedRow['email']);
        $this->assertEquals('some_password!', $seedRow[2]);
        $this->assertEquals('some_password!', $seedRow['password']);
    }

    public function testFullMigrationReduced()
    {
        $handler = $this->getHandler();
        $results = $handler->migrate([], null, true);

        $this->assertCount(5, $results);
        $this->assertTrue($results[0]->isSuccess());
        $this->assertNull($results[0]->getVersion());
        $this->assertTrue($results[1]->isSuccess());
        $this->assertNull($results[1]->getVersion());
        $this->assertTrue($results[2]->isSuccess());
        $this->assertNull($results[2]->getVersion());
        $this->assertTrue($results[3]->isSuccess());
        $this->assertNull($results[3]->getVersion());
        $this->assertTrue($results[4]->isSuccess());
        $this->assertNull($results[4]->getVersion());
    }

    public function testPartialMigration()
    {
        $handler = $this->getHandler();
        $results = $handler->migrate([], '2', false);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->isSuccess());
        $this->assertTrue($results[1]->isSuccess());
    }

    public function testMigrationWithMissed()
    {
        $handler = $this->getHandler();
        $handler->migrate([], '1', false);
        $handler->migrate(['1', '2'], '3', false);

        $results = $handler->migrate(['1', '3'], null, false);
        $this->assertCount(7, $results);
        $this->assertTrue($results[0]->isSuccess());
        $this->assertEquals('2', $results[0]->getVersion());
        $this->assertTrue($results[1]->isSuccess());
        $this->assertEquals('4', $results[1]->getVersion());
        $this->assertTrue($results[2]->isSuccess());
        $this->assertEquals('5', $results[2]->getVersion());
        $this->assertTrue($results[3]->isSuccess());
        $this->assertEquals('6', $results[3]->getVersion());
        $this->assertTrue($results[4]->isSuccess());
        $this->assertEquals('7', $results[4]->getVersion());
        $this->assertTrue($results[5]->isSuccess());
        $this->assertEquals('8', $results[5]->getVersion());
        $this->assertTrue($results[6]->isSuccess());
        $this->assertEquals('9', $results[6]->getVersion());
    }

    public function testFailingMigration()
    {
        $handler = $this->getHandler();
        $handler->migrate([], null, true);
        $results = $handler->migrate(['1'], '2', false);

        $this->assertCount(1, $results);
        $this->assertFalse($results[0]->isSuccess());
        $this->assertNotEmpty($results[0]->getSql());
        $this->assertNotEmpty($results[0]->getErrorInfo());
    }

    public function testRollback()
    {
        $handler = $this->getHandler();
        $handler->migrate([], null, false);
        $results = $handler->rollback(['1', '2', '3', '4', '5', '6', '7', '8', '9'], '2', false);

        $this->assertCount(6, $results);
        $this->assertTrue($results[0]->isSuccess());
        $this->assertEquals('9', $results[0]->getVersion());
    }

    public function testRollbackWithMissed()
    {
        $handler = $this->getHandler();
        $handler->migrate([], '1', false);
        $handler->migrate(['1', '2'], '6', false);

        $results = $handler->rollback(['1', '3', '4', '5', '6'], '1', false);
        $this->assertCount(4, $results);
        $this->assertTrue($results[0]->isSuccess());
        $this->assertEquals('6', $results[0]->getVersion());
        $this->assertTrue($results[1]->isSuccess());
        $this->assertEquals('5', $results[1]->getVersion());
        $this->assertTrue($results[2]->isSuccess());
        $this->assertEquals('4', $results[2]->getVersion());
        $this->assertTrue($results[3]->isSuccess());
        $this->assertEquals('3', $results[3]->getVersion());
    }

    public function testRollbackWithoutTarget()
    {
        $handler = $this->getHandler();
        $handler->migrate([], '3', false);
        $results = $handler->rollback(['1', '2', '3'], null, false);

        $this->assertCount(3, $results);
    }

    public function testFailingRollback()
    {
        $handler = $this->getHandler();
        $handler->migrate([], '2', false);
        $results = $handler->rollback(['1', '2', '3'], '1', false);

        $this->assertCount(1, $results);
        $this->assertFalse($results[0]->isSuccess());
        $this->assertNotEmpty($results[0]->getSql());
        $this->assertNotEmpty($results[0]->getErrorInfo());
    }

    private function getHandler(): Handler
    {
        $history = new History();

        $history->add('1', Migration::create('users')
            ->addColumn('id', ['type' => 'uuid', 'primary' => true])
            ->addColumn('username', ['type' => 'string'])
            ->addColumn('password', ['type' => 'string'])
            ->addColumn('meta', ['type' => 'json'])
        );

        $history->add('2', Migration::create('users_sessions')
            ->addColumn('id', ['type' => 'uuid', 'primary' => true])
            ->addColumn('user_id', ['type' => 'uuid'])
        );

        $history->add('3', Migration::alter('users')
            ->addColumn('email', ['type' => 'string', 'unique' => 'true', 'after' => 'id'])
            ->dropColumn('username')
        );

        $history->add('4', ViewMigration::create('user_counts')
            ->withBody('select count(*) as user_count from users')
        );

        $history->add('5', ViewMigration::alter('user_counts')
            ->withBody('select count(distinct id) as user_count from users')
        );

        $history->add('6', FunctionMigration::create('user_count_function')
            ->withReturnType('integer')
            ->readsSqlData(true)
            ->withBody('RETURN \'example value\';')
        );

        $history->add('7', ExecMigration::create('user_seed')
            ->withBody('INSERT INTO users (id, email, password) VALUES (1, \'bob@smith.com\',\'some_password!\');')
        );

        $history->add('8', ProcedureMigration::create('post_count')
            ->withDeterminism(false)
            ->withDataUse('READS SQL DATA')
            ->withBody("SELECT 'Total Posts:', COUNT(*) FROM posts;")
        );

        $history->add('9', ProcedureMigration::drop('post_count'));

        return new Handler($this->pdo, $history);
    }
}
