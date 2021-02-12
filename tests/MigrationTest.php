<?php

namespace Exo;

use Exo\Operation\TableOperation;
use LogicException;

/**
 * Class MigrationTest
 *
 * This class is duplicative to TableMigrationTest except that is uses the alias class "Migration"
 * in its implementations/instantiations. This is to ensure proper coverage & function of the alias class.
 *
 * This will be removed with the Migration class in a future release.
 *
 * @deprecated
 */
class MigrationTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateMigration()
    {
        $operation = Migration::create('users')
            ->addColumn('id', ['type' => 'uuid', 'primary' => true])
            ->addColumn('username', ['type' => 'string', 'length' => 64])
            ->addColumn('password', ['type' => 'string', 'length' => 128])
            ->getOperation();

        $this->assertEquals('users', $operation->getName());
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

        $this->assertEquals('users', $operation->getName());
        $this->assertEquals(TableOperation::ALTER, $operation->getOperation());
        $this->assertCount(3, $operation->getColumnOperations());
    }

    public function testDropMigration()
    {
        $operation = Migration::drop('users')
            ->getOperation();

        $this->assertEquals('users', $operation->getName());
        $this->assertEquals(TableOperation::DROP, $operation->getOperation());
    }

    public function testPreventModifyColumnDuringCreate()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot modify columns in a create migration.');

        Migration::create('users')
            ->modifyColumn('id', ['type' => 'string']);
    }

    public function testPreventDropColumnDuringCreate()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot drop columns in a create migration.');

        Migration::create('users')
            ->dropColumn('id');
    }

    public function testPreventAddColumnDuringDrop()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot add columns in a drop migration.');

        Migration::drop('users')
            ->addColumn('id', ['type' => 'string']);
    }

    public function testPreventModifyColumnDuringDrop()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot modify columns in a drop migration.');

        Migration::drop('users')
            ->modifyColumn('id', ['type' => 'string']);
    }

    public function testPreventDropColumnDuringDrop()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot drop columns in a drop migration.');

        Migration::drop('users')
            ->dropColumn('id');
    }

    public function testPreventDropIndexDuringCreate()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot drop indexes in a create migration.');

        Migration::create('users')
            ->dropIndex('email');
    }

    public function testPreventAddIndexDuringDrop()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot add indexes in a drop migration.');

        Migration::drop('users')
            ->addIndex('email', ['email']);
    }

    public function testPreventDropIndexDuringDrop()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot drop indexes in a drop migration.');

        Migration::drop('users')
            ->dropIndex('email');
    }
}
