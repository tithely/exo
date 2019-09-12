<?php

namespace Exo\Operation;

class TableOperationTest extends \PHPUnit\Framework\TestCase
{
    public function testApplyAlterToCreate()
    {
        $base = new TableOperation('users', TableOperation::CREATE, [
            new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid']),
            new ColumnOperation('username', ColumnOperation::ADD, ['type' => 'string', 'length' => 64])
        ], [
            new IndexOperation('username', IndexOperation::ADD, ['username'], ['unique' => true])
        ]);

        $operation = $base->apply(new TableOperation('users', TableOperation::ALTER, [
            new ColumnOperation('id', ColumnOperation::DROP, []),
            new ColumnOperation('email', ColumnOperation::ADD, ['type' => 'string', 'length' => 255, 'first' => true]),
            new ColumnOperation('username', ColumnOperation::MODIFY, ['type' => 'string', 'length' => 255])
        ], [
            new IndexOperation('username', IndexOperation::DROP, [], []),
            new IndexOperation('email', IndexOperation::ADD, ['email'], ['unique' => true])
        ]));

        $this->assertEquals('users', $operation->getTable());
        $this->assertEquals(TableOperation::CREATE, $operation->getOperation());
        $this->assertCount(2, $operation->getColumnOperations());
        $this->assertCount(1, $operation->getIndexOperations());

        $this->assertEquals('email', $operation->getColumnOperations()[0]->getColumn());
        $this->assertEquals(ColumnOperation::ADD, $operation->getColumnOperations()[0]->getOperation());
        $this->assertEquals(['type' => 'string', 'length' => 255], $operation->getColumnOperations()[0]->getOptions());

        $this->assertEquals('username', $operation->getColumnOperations()[1]->getColumn());
        $this->assertEquals(ColumnOperation::ADD, $operation->getColumnOperations()[1]->getOperation());
        $this->assertEquals(['type' => 'string', 'length' => 255], $operation->getColumnOperations()[1]->getOptions());

        $this->assertEquals('email', $operation->getIndexOperations()[0]->getName());
        $this->assertEquals(IndexOperation::ADD, $operation->getIndexOperations()[0]->getOperation());
    }

    public function testApplyDropToCreate()
    {
        $base = new TableOperation('users', TableOperation::CREATE, [
            new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid']),
            new ColumnOperation('username', ColumnOperation::ADD, ['type' => 'string', 'length' => 64])
        ], []);

        $operation = $base->apply(new TableOperation('users', TableOperation::DROP, [], []));

        $this->assertNull($operation);
    }

    public function testApplyAlterToAlter()
    {
        $base = new TableOperation('users', TableOperation::ALTER, [
            new ColumnOperation('id', ColumnOperation::DROP, []),
            new ColumnOperation('email', ColumnOperation::ADD, ['type' => 'string', 'length' => 255, 'first' => true]),
            new ColumnOperation('username', ColumnOperation::ADD, ['type' => 'string', 'length' => 255])
        ], [
            new IndexOperation('email_username', ColumnOperation::ADD, ['email', 'username'], ['unique' => true])
        ]);

        $operation = $base->apply(new TableOperation('users', TableOperation::ALTER, [
            new ColumnOperation('email', ColumnOperation::DROP, []),
            new ColumnOperation('username', ColumnOperation::MODIFY, ['type' => 'string', 'length' => 64])
        ], []));

        $this->assertEquals('users', $operation->getTable());
        $this->assertEquals(TableOperation::ALTER, $operation->getOperation());
        $this->assertCount(2, $operation->getColumnOperations());
        $this->assertCount(1, $operation->getIndexOperations());

        $this->assertEquals('id', $operation->getColumnOperations()[0]->getColumn());
        $this->assertEquals(ColumnOperation::DROP, $operation->getColumnOperations()[0]->getOperation());

        $this->assertEquals('username', $operation->getColumnOperations()[1]->getColumn());
        $this->assertEquals(ColumnOperation::ADD, $operation->getColumnOperations()[1]->getOperation());
        $this->assertEquals(['type' => 'string', 'length' => 64], $operation->getColumnOperations()[1]->getOptions());

        $this->assertEquals('email_username', $operation->getIndexOperations()[0]->getName());
        $this->assertEquals(IndexOperation::ADD, $operation->getIndexOperations()[0]->getOperation());
        $this->assertEquals(['username'], $operation->getIndexOperations()[0]->getColumns());
        $this->assertEquals(['unique' => true], $operation->getIndexOperations()[0]->getOptions());
    }

    public function testApplyDropToAlter()
    {
        $base = new TableOperation('users', TableOperation::ALTER, [
            new ColumnOperation('id', ColumnOperation::DROP, []),
            new ColumnOperation('email', ColumnOperation::ADD, ['type' => 'string', 'length' => 255, 'first' => true]),
            new ColumnOperation('username', ColumnOperation::MODIFY, ['type' => 'string', 'length' => 255])
        ], []);

        $drop = new TableOperation('users', TableOperation::DROP, [], []);

        $operation = $base->apply($drop);
        $this->assertEquals($drop, $operation);
    }

    public function testReverseCreate()
    {
        $base = new TableOperation('users', TableOperation::CREATE, [
            new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid']),
            new ColumnOperation('username', ColumnOperation::ADD, ['type' => 'string', 'length' => 64])
        ], []);

        $operation = $base->reverse();

        $this->assertEquals('users', $operation->getTable());
        $this->assertEquals(TableOperation::DROP, $operation->getOperation());
    }

    public function testReverseAlter()
    {
        $base = new TableOperation('users', TableOperation::ALTER, [
            new ColumnOperation('id', ColumnOperation::DROP, []),
            new ColumnOperation('email', ColumnOperation::ADD, ['type' => 'string', 'length' => 255, 'first' => true]),
            new ColumnOperation('username', ColumnOperation::MODIFY, ['type' => 'string', 'length' => 255])
        ], []);

        $create = new TableOperation('users', TableOperation::CREATE, [
            new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid']),
            new ColumnOperation('username', ColumnOperation::ADD, ['type' => 'string', 'length' => 64])
        ], []);

        $operation = $base->reverse($create);

        $this->assertEquals('users', $operation->getTable());
        $this->assertEquals(TableOperation::ALTER, $operation->getOperation());

        $this->assertEquals('id', $operation->getColumnOperations()[0]->getColumn());
        $this->assertEquals(ColumnOperation::ADD, $operation->getColumnOperations()[0]->getOperation());
        $this->assertEquals(['type' => 'uuid'], $operation->getColumnOperations()[0]->getOptions());

        $this->assertEquals('email', $operation->getColumnOperations()[1]->getColumn());
        $this->assertEquals(ColumnOperation::DROP, $operation->getColumnOperations()[1]->getOperation());

        $this->assertEquals('username', $operation->getColumnOperations()[2]->getColumn());
        $this->assertEquals(ColumnOperation::MODIFY, $operation->getColumnOperations()[2]->getOperation());
        $this->assertEquals(['type' => 'string', 'length' => 64], $operation->getColumnOperations()[2]->getOptions());
    }

    public function testReverseDrop()
    {
        $base = new TableOperation('users', TableOperation::DROP, [], []);

        $create = new TableOperation('users', TableOperation::CREATE, [
            new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid']),
            new ColumnOperation('username', ColumnOperation::ADD, ['type' => 'string', 'length' => 64])
        ], []);

        $operation = $base->reverse($create);

        $this->assertEquals($create, $operation);
    }
}
