<?php

namespace Exo;

use Exo\Operation\ParameterOperation;
use Exo\Operation\ProcedureOperation;
use PHPUnit\Framework\TestCase;
use LogicException;

class ProcedureMigrationTest extends TestCase
{
    const BODY_ONE = "SELECT COUNT(*) INTO total FROM posts;";
    const BODY_TWO = "UPDATE users SET email = userEmail WHERE id = uid;";

    public function testCreateFunction()
    {
        $operation = ProcedureMigration::create('my_procedure')
            ->isDeterministic(true)
            ->addInParameter('inParameter', ['type' => 'integer'])
            ->addOutParameter('outParameter', ['type' => 'integer'])
            ->withBody(self::BODY_ONE)
            ->getOperation();

        $this->assertEquals('my_procedure', $operation->getName());

        $this->assertEquals(ProcedureOperation::CREATE, $operation->getOperation());

        $this->assertCount(1, $operation->getInParameterOperations());
        $this->assertEquals('inParameter', $operation->getInParameterOperations()[0]->getName());
        $this->assertEquals(ParameterOperation::ADD, $operation->getInParameterOperations()[0]->getOperation());
        $this->assertEquals(['type' => 'integer'], $operation->getInParameterOperations()[0]->getOptions());

        $this->assertCount(1, $operation->getOutParameterOperations());
        $this->assertEquals('outParameter', $operation->getOutParameterOperations()[0]->getName());
        $this->assertEquals(ParameterOperation::ADD, $operation->getOutParameterOperations()[0]->getOperation());
        $this->assertEquals(['type' => 'integer'], $operation->getOutParameterOperations()[0]->getOptions());

        $this->assertEquals(self::BODY_ONE, $operation->getBody());
    }

    public function testDropProcedure()
    {
        $operation = ProcedureMigration::drop('my_deprecated_function')
            ->getOperation();

        $this->assertEquals('my_deprecated_function', $operation->getName());
        $this->assertEquals(ProcedureOperation::DROP, $operation->getOperation());
        $this->assertNull($operation->getBody());
    }

    public function testPreventModifyBodyDuringDrop()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Procedure body can only be set in a procedure create migration.');

        ProcedureMigration::drop('my_deprecated_function')
            ->withBody(self::BODY_TWO);
    }

    public function testPreventAddInParametersDuringDrop()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot add parameters in a procedure drop migration.');

        ProcedureMigration::drop('my_deprecated_function')
            ->addInParameter('someParam');
    }

    public function testPreventAddOutParametersDuringDrop()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot add parameters in a procedure drop migration.');

        ProcedureMigration::drop('my_deprecated_function')
            ->addOutParameter('someParam');
    }

    public function testPreventIsDeterministicDuringDrop()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot set deterministic property in a procedure drop migration.');

        ProcedureMigration::drop('my_deprecated_function')
            ->isDeterministic(true);
    }

    public function testPreventReadsSqlDataDuringDrop()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot set readsSqlData property in a procedure drop migration.');

        ProcedureMigration::drop('my_deprecated_function')
            ->readsSqlData('CONTAINS SQL');
    }
}
