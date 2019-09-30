<?php

namespace Exo;

class MysqlHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PDO
     */
    private $pdo;

    public function setUp(): void
    {
        $this->pdo = new \PDO('mysql:dbname=test;host=127.0.0.1', 'root', '');
        $this->pdo->exec('DROP TABLE IF EXISTS test');
    }

    public function tearDown(): void
    {
        $this->pdo = null;
    }

    public function testFullMigration()
    {
        $handler = $this->getHandler();
        $results = $handler->migrate(null, null, false);

        $this->assertCount(3, $results);
    }

    public function testFullMigrationReduced()
    {
        $handler = $this->getHandler();
        $results = $handler->migrate(null, null, true);

        $this->assertCount(2, $results);
    }

    private function getHandler()
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

        return new Handler($this->pdo, $history);
    }
}
