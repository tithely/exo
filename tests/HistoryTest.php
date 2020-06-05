<?php

namespace Exo;

use Exo\Operation\TableOperation;

class HistoryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetVersions()
    {
        $versions = $this->getHistory()->getVersions();

        $this->assertEquals(['1', '2', '3'], $versions);
    }

    public function testPlay()
    {
        $operations = $this
            ->getHistory()
            ->play('2', '3');

        $this->assertCount(2, $operations);
        $this->assertEquals('users', $operations[0]->getName());
        $this->assertEquals(TableOperation::ALTER, $operations[0]->getOperation());
        $this->assertEquals('posts', $operations[1]->getName());
        $this->assertEquals(TableOperation::CREATE, $operations[1]->getOperation());
    }

    public function testPlayReduced()
    {
        $operations = $this
            ->getHistory()
            ->play('1', '3', true);

        $this->assertCount(2, $operations);
        $this->assertEquals('users', $operations[0]->getName());
        $this->assertEquals(TableOperation::CREATE, $operations[0]->getOperation());
        $this->assertEquals('posts', $operations[1]->getName());
        $this->assertEquals(TableOperation::CREATE, $operations[1]->getOperation());
    }

    public function testRewind()
    {
        $operations = $this
            ->getHistory()
            ->rewind('3', '2');

        $this->assertCount(2, $operations);
        $this->assertEquals('posts', $operations[0]->getName());
        $this->assertEquals(TableOperation::DROP, $operations[0]->getOperation());
        $this->assertEquals('users', $operations[1]->getName());
        $this->assertEquals(TableOperation::ALTER, $operations[1]->getOperation());
    }

    public function testRewindReduced()
    {
        $operations = $this
            ->getHistory()
            ->rewind('3', '1', true);

        $this->assertCount(2, $operations);
        $this->assertEquals('posts', $operations[0]->getName());
        $this->assertEquals(TableOperation::DROP, $operations[0]->getOperation());
        $this->assertEquals('users', $operations[1]->getName());
        $this->assertEquals(TableOperation::DROP, $operations[1]->getOperation());
    }

    private function getHistory()
    {
        $history = new History();

        $history->add('1', Migration::create('users')
            ->addColumn('id', ['type' => 'uuid'])
            ->addColumn('username', ['type' => 'string', 'length' => 64])
            ->addColumn('password', ['type' => 'string', 'length' => 255])
        );

        $history->add('2', Migration::alter('users')
            ->addColumn('email', ['type' => 'string', 'length' => 255, 'after' => 'id'])
            ->dropColumn('username')
        );

        $history->add('3', Migration::create('posts')
            ->addColumn('id', ['type' => 'uuid'])
            ->addColumn('user_id', ['type' => 'uuid'])
            ->addColumn('title', ['type' => 'text'])
            ->addColumn('body', ['type' => 'text'])
        );

        return $history;
    }
}
