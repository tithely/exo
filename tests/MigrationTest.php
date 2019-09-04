<?php

namespace Exo;

use Exo\Operation\TableOperation;

class MigrationTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateMigration()
    {
        $operation = Migration::create('users')
            ->addColumn('id', ['type' => 'uuid', 'primary' => true])
            ->addColumn('username', ['type' => 'string', 'length' => 64])
            ->addColumn('password', ['type' => 'string', 'length' => 128])
            ->getOperation();

        $this->assertEquals('users', $operation->getTable());
        $this->assertEquals(TableOperation::CREATE, $operation->getOperation());
        $this->assertCount(3, $operation->getColumnOperations());
    }

    public function testAlterMigration()
    {
        $operation = Migration::alter('users')
            ->addColumn('email', ['type' => 'string', 'length' => 255])
            ->modifyColumn('password', ['type' => 'string', 'length' => 255])
            ->dropColumn('username')
            ->getOperation();

        $this->assertEquals('users', $operation->getTable());
        $this->assertEquals(TableOperation::ALTER, $operation->getOperation());
        $this->assertCount(3, $operation->getColumnOperations());
    }

    public function testDropMigration()
    {
        $operation = Migration::drop('users')
            ->getOperation();

        $this->assertEquals('users', $operation->getTable());
        $this->assertEquals(TableOperation::DROP, $operation->getOperation());
    }

    public function testPreventModifyDuringCreate()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot modify columns in a create migration.');

        Migration::create('users')
            ->modifyColumn('id', ['type' => 'string']);
    }

    public function testPreventDropDuringCreate()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot drop columns in a create migration.');

        Migration::create('users')
            ->dropColumn('id');
    }

    public function testPreventAddDuringDrop()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot add columns in a drop migration.');

        Migration::drop('users')
            ->addColumn('id', ['type' => 'string']);
    }

    public function testPreventModifyDuringDrop()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot modify columns in a drop migration.');

        Migration::drop('users')
            ->modifyColumn('id', ['type' => 'string']);
    }

    public function testPreventDropDuringDrop()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot drop columns in a drop migration.');

        Migration::drop('users')
            ->dropColumn('id');
    }
}
