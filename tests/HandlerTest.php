<?php

namespace Exo;

use Exo\Util\Finder;
use PDO;
use PHPUnit\Framework\TestCase;

class HandlerTest extends TestCase
{
    /**
     * @var PDO|null
     */
    protected ?PDO $pdo;

    /**
     * @var array
     */
    private array $versions = [];

    public function testHistoryVersionCount()
    {
        $history = $this->getHistory();
        $this->assertCount(9, $history->getVersions());
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }

    protected function testFullMigration()
    {
        $handler = $this->getHandler();
        $results = $handler->migrate([], null, false);

        // Validate all versions were a success
        $this->assertCount(count($this->versions), $results, $results[count($results) - 1]->getErrorInfo()[2] ?? '');
        foreach ($results as $index => $result) {
            $this->assertTrue($result->isSuccess(), $result->getErrorInfo()[2] ?? '');
            $this->assertEquals($this->versions[$index], $result->getVersion());
        }

        // Verify Exec Results
        $seedCheck = $this->pdo->query('SELECT * FROM users')->fetchAll();
        $this->assertEquals($this->versions[6], $results[6]->getVersion());

        // 1 Row Expected
        $this->assertCount(1, $seedCheck);

        // Get First Row
        $seedRow = $seedCheck[0];

        // Validate Data
        $this->assertEquals('1', trim($seedRow['id']));
        $this->assertEquals('bob@smith.com', $seedRow['email']);
        $this->assertEquals('some_password!', $seedRow['password']);
    }

    protected function testFullMigrationReduced()
    {
        $handler = $this->getHandler();
        $results = $handler->migrate([], null, true);

        // Validate all versions were a success
        $this->assertCount(7, $results);
        foreach ($results as $result) {
            $this->assertTrue($result->isSuccess());
        }
    }

    protected function testPartialMigration()
    {
        $handler = $this->getHandler();
        $results = $handler->migrate([], $this->versions[1], false);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->isSuccess());
        $this->assertTrue($results[1]->isSuccess());
    }

    protected function testMigrationWithMissed()
    {
        $handler = $this->getHandler();
        $handler->migrate([], $this->versions[0], false);
        $handler->migrate(array_slice($this->versions, 0, 2), $this->versions[2], false);

        $results = $handler->migrate([$this->versions[0], $this->versions[2]], null, false);
        $this->assertCount(7, $results);
        $this->assertTrue($results[0]->isSuccess());
        $this->assertEquals($this->versions[1], $results[0]->getVersion());
        $this->assertTrue($results[1]->isSuccess());
        $this->assertEquals($this->versions[3], $results[1]->getVersion());
        $this->assertTrue($results[2]->isSuccess());
        $this->assertEquals($this->versions[4], $results[2]->getVersion());
        $this->assertTrue($results[3]->isSuccess());
        $this->assertEquals($this->versions[5], $results[3]->getVersion());
        $this->assertTrue($results[4]->isSuccess());
        $this->assertEquals($this->versions[6], $results[4]->getVersion());
        $this->assertTrue($results[5]->isSuccess());
        $this->assertEquals($this->versions[7], $results[5]->getVersion());
        $this->assertTrue($results[6]->isSuccess());
        $this->assertEquals($this->versions[8], $results[6]->getVersion());
    }

    protected function testFailingMigration()
    {
        $handler = $this->getHandler();
        $handler->migrate([], null, true);
        $results = $handler->migrate(array_slice($this->versions, 0, 1), $this->versions[1], false);

        $this->assertCount(1, $results);
        $this->assertFalse($results[0]->isSuccess());
        $this->assertNotEmpty($results[0]->getSql());
        $this->assertNotEmpty($results[0]->getErrorInfo());
    }

    protected function testRollback()
    {
        $handler = $this->getHandler();
        $handler->migrate([], null, false);
        $results = $handler->rollback(array_slice($this->versions, 0, 8), $this->versions[1], false);

        $this->assertCount(5, $results);
        $this->assertTrue($results[0]->isSuccess());
        $this->assertEquals($this->versions[7], $results[0]->getVersion());
    }

    protected function testRollbackWithMissed()
    {
        $handler = $this->getHandler();
        $handler->migrate([], '1', false);
        $handler->migrate(array_slice($this->versions, 0, 2), $this->versions[5], false);

        $results = $handler->rollback(array_slice($this->versions, 0, 6), $this->versions[0], false);
        $this->assertCount(5, $results);
        $this->assertTrue($results[0]->isSuccess());
        $this->assertEquals($this->versions[5], $results[0]->getVersion());
        $this->assertTrue($results[1]->isSuccess());
        $this->assertEquals($this->versions[4], $results[1]->getVersion());
        $this->assertTrue($results[2]->isSuccess());
        $this->assertEquals($this->versions[3], $results[2]->getVersion());
        $this->assertTrue($results[3]->isSuccess());
        $this->assertEquals($this->versions[2], $results[3]->getVersion());
    }

    protected function testRollbackWithoutTarget()
    {
        $handler = $this->getHandler();
        $handler->migrate([], $this->versions[2], false);
        $results = $handler->rollback(array_slice($this->versions, 0, 3), null, false);

        $this->assertCount(3, $results);
    }

    protected function testFailingRollback()
    {
        $handler = $this->getHandler();
        $handler->migrate([], $this->versions[1], false);
        $results = $handler->rollback(array_slice($this->versions, 0, 3), $this->versions[1], false);

        $this->assertCount(1, $results);
        $this->assertFalse($results[0]->isSuccess());
        $this->assertNotEmpty($results[0]->getSql());
        $this->assertNotEmpty($results[0]->getErrorInfo());
    }

    private function getHandler(): Handler
    {
        $history = $this->getHistory();
        $this->versions = $history->getVersions();

        return new Handler($this->pdo, $history);
    }

    private function getHistory(): History
    {
        $finder = new Finder(
            ['tenant_database_name' => ($this instanceof MysqlHandlerTest) ? 'test' : 'public']
        );
        return $finder->fromPath(__DIR__ . '/Migrations');
    }
}
