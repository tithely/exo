<?php

namespace Exo;

use Exo\Tests\Traits\UsesYamlConfig;
use PDO;

class MysqlHandlerTest extends HandlerTest
{
    use UsesYamlConfig;

    protected function setUp(): void
    {
        $mysql = self::yaml('handlers.mysql');
        try {
            $this->pdo = new PDO(
                sprintf('mysql:dbname=%s;host=%s;port=%s', $mysql['name'], $mysql['host'], $mysql['port']),
                $mysql['user'],
                $mysql['pass']
            );
            $this->drop();
        } catch (\Exception $e) {
            $this->markTestSkipped('No Connection to mySQL');
        }
    }

    private function drop()
    {
        $this->pdo->exec('DROP TABLE IF EXISTS users;');
        $this->pdo->exec('DROP TABLE IF EXISTS posts;');
        $this->pdo->exec('DROP VIEW IF EXISTS user_counts;');
        $this->pdo->exec('DROP FUNCTION IF EXISTS user_level;');
        $this->pdo->exec('DROP FUNCTION IF EXISTS user_count_function;');
        $this->pdo->exec('DROP PROCEDURE IF EXISTS post_count;');
        $this->pdo->exec('DROP TABLE IF EXISTS all_columns_and_types;');
    }

    public function testFullMigration()
    {
        parent::testFullMigration();
    }

    public function testFullMigrationReduced()
    {
        parent::testFullMigrationReduced();
    }

    public function testPartialMigration()
    {
        parent::testPartialMigration();
    }

    public function testMigrationWithMissed()
    {
        parent::testMigrationWithMissed();
    }

    public function testFailingMigration()
    {
        parent::testFailingMigration();
    }

    public function testRollback()
    {
        parent::testRollback();
    }

    public function testRollbackWithMissed()
    {
        parent::testRollbackWithMissed();
    }

    public function testRollbackWithoutTarget()
    {
        parent::testRollbackWithoutTarget();
    }

    public function testFailingRollback()
    {
        parent::testFailingRollback();
    }
}