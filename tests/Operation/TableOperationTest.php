<?php

namespace Exo\Operation;

class TableOperationTest extends \PHPUnit\Framework\TestCase
{
    public function testApplyAlterToCreate()
    {
        $base = new TableOperation('users', TableOperation::CREATE, [
            new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid']),
            new ColumnOperation('username', ColumnOperation::ADD, ['type' => 'string', 'length' => 64])
        ]);

        $operation = $base->apply(new TableOperation('users', TableOperation::ALTER, [
            new ColumnOperation('id', ColumnOperation::DROP, []),
            new ColumnOperation('email', ColumnOperation::ADD, ['type' => 'string', 'length' => 255, 'first' => true]),
            new ColumnOperation('username', ColumnOperation::MODIFY, ['type' => 'string', 'length' => 255])
        ]));

        $this->assertEquals('users', $operation->getTable());
        $this->assertEquals(TableOperation::CREATE, $operation->getOperation());
        $this->assertCount(2, $operation->getColumnOperations());

        $this->assertEquals('email', $operation->getColumnOperations()[0]->getColumn());
        $this->assertEquals(ColumnOperation::ADD, $operation->getColumnOperations()[0]->getOperation());
        $this->assertEquals(['type' => 'string', 'length' => 255, 'first' => true], $operation->getColumnOperations()[0]->getOptions());

        $this->assertEquals('username', $operation->getColumnOperations()[1]->getColumn());
        $this->assertEquals(ColumnOperation::ADD, $operation->getColumnOperations()[1]->getOperation());
        $this->assertEquals(['type' => 'string', 'length' => 255], $operation->getColumnOperations()[1]->getOptions());
    }
}
