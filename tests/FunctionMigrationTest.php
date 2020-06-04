<?php

namespace Exo;

use Exo\Operation\FunctionOperation;
use Exo\Operation\ParameterOperation;
use Exo\Operation\ReturnTypeOperation;
use Exo\Operation\VariableOperation;

class FunctionMigrationTest extends \PHPUnit\Framework\TestCase
{
    const BODY_ONE = "
        IF credit > 50000 THEN
        SET customerLevel = 'PLATINUM';
        ELSEIF (credit >= 50000 AND
        credit <= 10000) THEN
        SET customerLevel = 'GOLD';
        ELSEIF credit < 10000 THEN
        SET customerLevel = 'SILVER';
        END IF;
        -- return the customer level
        RETURN (customerLevel);";

    const BODY_TWO = "
        IF credit > 50000 THEN
        SET customerLevel = 'PLATINUM';
        ELSEIF credit < 10000 THEN
        SET customerLevel = 'SILVER';
        END IF;
        -- return the customer level
        RETURN (customerLevel);";

    public function testCreateFunction()
    {
        $operation = FunctionMigration::create('my_function')
            ->isDeterministic(true)
            ->withReturnType('string', ['type' => 'string', 'length' => 256])
            ->addParameter('someParameters', ['type' => 'integer'])
            ->addVariable('someVariable', ['type' => 'string', 'length' => 64])
            ->withBody(self::BODY_ONE)
            ->getOperation();

        $this->assertEquals('my_function', $operation->getName());

        $this->assertEquals(FunctionOperation::CREATE, $operation->getOperation());

        $this->assertEquals('string', $operation->getReturnType()->getType());
        $this->assertEquals(ReturnTypeOperation::ADD, $operation->getReturnType()->getOperation());
        $this->assertEquals(['type' => 'string', 'length' => 256], $operation->getReturnType()->getOptions());

        $this->assertCount(1, $operation->getParameterOperations());
        $this->assertEquals('someParameters', $operation->getParameterOperations()[0]->getParameter());
        $this->assertEquals(ParameterOperation::ADD, $operation->getParameterOperations()[0]->getOperation());
        $this->assertEquals(['type' => 'integer'], $operation->getParameterOperations()[0]->getOptions());

        $this->assertCount(1, $operation->getVariableOperations());
        $this->assertEquals('someVariable', $operation->getVariableOperations()[0]->getVariable());
        $this->assertEquals(VariableOperation::ADD, $operation->getVariableOperations()[0]->getOperation());
        $this->assertEquals(['type' => 'string', 'length' => 64], $operation->getVariableOperations()[0]->getOptions());

        $this->assertEquals(self::BODY_ONE, $operation->getBody());
    }

    public function testReplaceFunction()
    {
        $operation = FunctionMigration::replace('my_replacement_function')
            ->withReturnType('string', ['type' => 'string', 'length' => 32])
            ->withBody(self::BODY_TWO)
            ->getOperation();

        $this->assertEquals('my_replacement_function', $operation->getName());

        $this->assertEquals(FunctionOperation::REPLACE, $operation->getOperation());

        $this->assertEquals('string', $operation->getReturnType()->getType());
        $this->assertEquals(ReturnTypeOperation::ADD, $operation->getReturnType()->getOperation());
        $this->assertEquals(['type' => 'string', 'length' => 32], $operation->getReturnType()->getOptions());

        $this->assertCount(0, $operation->getParameterOperations());

        $this->assertCount(0, $operation->getVariableOperations());

        $this->assertEquals(self::BODY_TWO, $operation->getBody());
    }

    public function testDropFunction()
    {
        $operation = FunctionMigration::drop('my_deprecated_function')
            ->getOperation();

        $this->assertEquals('my_deprecated_function', $operation->getName());
        $this->assertEquals(FunctionOperation::DROP, $operation->getOperation());
        $this->assertNull($operation->getBody());
    }

    public function testPreventModifyBodyDuringDrop()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot set view body in a view drop migration.');

        FunctionMigration::drop('my_deprecated_function')
            ->withBody(self::BODY_TWO);
    }

    public function testPreventAddParametersDuringDrop()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot add parameters in a view drop migration.');

        FunctionMigration::drop('my_deprecated_function')
            ->addParameter('someParam');
    }

    public function testPreventAddVariableDuringDrop()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot add variables in a view drop migration.');

        FunctionMigration::drop('my_deprecated_function')
            ->addVariable('someVar');
    }
}
