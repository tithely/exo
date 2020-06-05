<?php

namespace Exo\Operation;

use phpDocumentor\Reflection\Types\Integer;

class FunctionOperationTest extends \PHPUnit\Framework\TestCase
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

    public function testCreate()
    {
        $operation = new FunctionOperation('customer_level', FunctionOperation::CREATE,
            new ReturnTypeOperation('string', ReturnTypeOperation::ADD),
            true,
            [
                new ParameterOperation('arg1', ParameterOperation::ADD),
                new ParameterOperation('arg2', ParameterOperation::ADD, ['type' => 'integer']),
                new ParameterOperation('arg3', ParameterOperation::ADD, ['type' => 'boolean'])
            ],
            [new VariableOperation('customerLevel', VariableOperation::ADD, ['type' => 'string', 'length' => 20])],
            self::BODY_ONE
        );

        $this->assertEquals('customer_level', $operation->getName());

        $this->assertEquals('string', $operation->getReturnType()->getType());
        $this->assertEquals(ReturnTypeOperation::ADD, $operation->getReturnType()->getOperation());
        $this->assertEquals([], $operation->getReturnType()->getOptions());

        $this->assertTrue($operation->getDeterministic());

        $this->assertCount(3, $operation->getParameterOperations());

        $this->assertEquals('arg1', $operation->getParameterOperations()[0]->getName());
        $this->assertEquals(ParameterOperation::ADD, $operation->getParameterOperations()[0]->getOperation());
        $this->assertEquals([], $operation->getParameterOperations()[0]->getOptions());

        $this->assertEquals('arg2', $operation->getParameterOperations()[1]->getName());
        $this->assertEquals(ParameterOperation::ADD, $operation->getParameterOperations()[1]->getOperation());
        $this->assertEquals(['type' => 'integer'], $operation->getParameterOperations()[1]->getOptions());

        $this->assertEquals('arg3', $operation->getParameterOperations()[2]->getName());
        $this->assertEquals(ParameterOperation::ADD, $operation->getParameterOperations()[2]->getOperation());
        $this->assertEquals(['type' => 'boolean'], $operation->getParameterOperations()[2]->getOptions());

        $this->assertCount(1, $operation->getVariableOperations());

        $this->assertEquals('customerLevel', $operation->getVariableOperations()[0]->getName());
        $this->assertEquals(VariableOperation::ADD, $operation->getVariableOperations()[0]->getOperation());
        $this->assertEquals(['type' => 'string', 'length' => 20], $operation->getVariableOperations()[0]->getOptions());

        $this->assertEquals(self::BODY_ONE, $operation->getBody());
    }

    public function testReplace()
    {
        $base = new FunctionOperation('customer_level', FunctionOperation::CREATE,
            new ReturnTypeOperation('string', ReturnTypeOperation::ADD),
            true,
            [
                new ParameterOperation('arg1', ParameterOperation::ADD),
                new ParameterOperation('arg2', ParameterOperation::ADD, ['type' => 'integer']),
                new ParameterOperation('arg3', ParameterOperation::ADD), ['type' => 'boolean']
            ],
            [new VariableOperation('customerLevel', VariableOperation::ADD, ['type' => 'string', 'length' => 20])],
            self::BODY_ONE
        );

        $new = new FunctionOperation('customer_level', FunctionOperation::REPLACE,
            new ReturnTypeOperation('integer', ReturnTypeOperation::ADD),
            false,
            [],
            [],
            self::BODY_TWO
        );

        $operation = $base->apply($new);

        $this->assertEquals('customer_level', $operation->getName());

        $this->assertEquals('integer', $operation->getReturnType()->getType());
        $this->assertEquals(ReturnTypeOperation::ADD, $operation->getReturnType()->getOperation());
        $this->assertEquals([], $operation->getReturnType()->getOptions());

        $this->assertFalse($operation->getDeterministic());

        $this->assertCount(0, $operation->getParameterOperations());

        $this->assertCount(0, $operation->getVariableOperations());

        $this->assertEquals(self::BODY_TWO, $operation->getBody());
    }

    public function testApplyDropToCreateOrReplace()
    {
        $base = new FunctionOperation('any_name', FunctionOperation::REPLACE,
            new ReturnTypeOperation('string', ReturnTypeOperation::ADD),
            true,
            [],
            [],
            self::BODY_ONE
        );

        $operation = $base->apply(new FunctionOperation('any_name', FunctionOperation::DROP));

        $this->assertNull($operation);
    }

    public function testReverseCreate()
    {
        $base = new FunctionOperation(
            'method_three',
            FunctionOperation::CREATE,
            new ReturnTypeOperation('string', ReturnTypeOperation::ADD)
        );

        $operation = $base->reverse();

        $this->assertEquals('method_three', $operation->getName());
        $this->assertEquals(FunctionOperation::DROP, $operation->getOperation());
    }

    public function testReverseReplace()
    {
        $base = new FunctionOperation(
            'method_four',
            FunctionOperation::REPLACE,
            new ReturnTypeOperation('integer', ReturnTypeOperation::ADD),
            false,
            [
                new ParameterOperation('id', ParameterOperation::ADD, []),
                new ParameterOperation('email', ParameterOperation::ADD, ['type' => 'string', 'length' => 255]),
                new ParameterOperation('username', ParameterOperation::ADD, ['type' => 'string', 'length' => 255])
            ],
            [
                new VariableOperation('username', VariableOperation::ADD, []),
                new VariableOperation('email', VariableOperation::ADD, ['type' => 'string'])
            ],
            self::BODY_ONE
        );

        $create = new FunctionOperation(
            'method_four',
            FunctionOperation::REPLACE,
            new ReturnTypeOperation('string', ReturnTypeOperation::ADD),
            true,
            [
                new ParameterOperation('id', ParameterOperation::ADD, ['type' => 'uuid']),
                new ParameterOperation('username', ParameterOperation::ADD, ['type' => 'string', 'length' => 64])
            ],
            [
                new VariableOperation('username', VariableOperation::ADD, ['type' => 'integer'])
            ],
            self::BODY_TWO
        );

        $operation = $base->reverse($create);

        $this->assertEquals('method_four', $operation->getName());
        $this->assertEquals(FunctionOperation::REPLACE, $operation->getOperation());

        $this->assertCount(2, $operation->getParameterOperations());

        $this->assertEquals('id', $operation->getParameterOperations()[0]->getName());
        $this->assertEquals(ParameterOperation::ADD, $operation->getParameterOperations()[0]->getOperation());
        $this->assertEquals(['type' => 'uuid'], $operation->getParameterOperations()[0]->getOptions());

        $this->assertEquals('username', $operation->getParameterOperations()[1]->getName());
        $this->assertEquals(ParameterOperation::ADD, $operation->getParameterOperations()[1]->getOperation());
        $this->assertEquals(['type' => 'string', 'length' => 64], $operation->getParameterOperations()[1]->getOptions());

        $this->assertCount(1, $operation->getVariableOperations());

        $this->assertEquals('username', $operation->getVariableOperations()[0]->getName());
        $this->assertEquals(VariableOperation::ADD, $operation->getVariableOperations()[0]->getOperation());
        $this->assertEquals(['type' => 'integer'], $operation->getVariableOperations()[0]->getOptions());
    }

    public function testReverseDrop()
    {
        $base = new FunctionOperation('method_five', FunctionOperation::DROP);

        $previous = new FunctionOperation(
            'method_five',
            FunctionOperation::REPLACE,
            new ReturnTypeOperation('integer', ReturnTypeOperation::ADD),
            false,
            [
                new ParameterOperation('id', ParameterOperation::ADD, []),
                new ParameterOperation('email', ParameterOperation::ADD, ['type' => 'string', 'length' => 255, 'first' => true]),
                new ParameterOperation('username', ParameterOperation::ADD, ['type' => 'string', 'length' => 255])
            ],
            [
                new VariableOperation('username', VariableOperation::ADD, []),
                new VariableOperation('email', VariableOperation::ADD, ['type' => 'string'])
            ],
            self::BODY_ONE
        );

        $operation = $base->reverse($previous);

        $this->assertEquals($previous, $operation);
    }
}
