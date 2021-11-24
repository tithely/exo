<?php

namespace Exo;

use Exo\Tests\Traits\UsesYamlConfig;
use PDO;

class PostgresqlHandlerTest extends HandlerTest
{
    use UsesYamlConfig;

    protected function setUp(): void
    {
        $pgsql = self::yaml('handlers.pgsql');
        try {
            $this->pdo = new PDO(
                sprintf('pgsql:dbname=%s;host=%s;port=%s', $pgsql['name'], $pgsql['host'], $pgsql['port']),
                $pgsql['user'],
                $pgsql['pass']
            );
            $this->drop();
        } catch (\Exception $e) {
            $this->markTestSkipped('No Connection to postgreSQL');
        }
    }

    private function drop()
    {
        $this->pdo->exec('DROP TABLE IF EXISTS users CASCADE;');
        $this->pdo->exec('DROP TABLE IF EXISTS posts CASCADE;');
        $this->pdo->exec('DROP VIEW IF EXISTS user_counts CASCADE;');
        $this->pdo->exec('DROP FUNCTION IF EXISTS user_level CASCADE;');
        $this->pdo->exec('DROP FUNCTION IF EXISTS user_count_function CASCADE;');
        $this->pdo->exec('DROP PROCEDURE IF EXISTS post_count CASCADE;');
        $this->pdo->exec('DROP TABLE IF EXISTS all_columns_and_types CASCADE;');
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
