<?php

namespace Exo\Operation;

class FunctionOperationTest extends \PHPUnit\Framework\TestCase
{
    const BODY_ONE = "
        DECLARE customerLevel VARCHAR(20);
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
        DECLARE customerLevel VARCHAR(20);
        IF credit > 50000 THEN
        SET customerLevel = 'PLATINUM';
        ELSEIF credit < 10000 THEN
        SET customerLevel = 'SILVER';
        END IF;
        -- return the customer level
        RETURN (customerLevel);";

    public function testApplyAlterToCreate()
    {
        $base = new FunctionOperation('user_view', FunctionOperation::CREATE, self::BODY_ONE);

        $operation = $base->apply(new FunctionOperation('user_view', FunctionOperation::ALTER, self::BODY_TWO));

        $this->assertEquals('user_view', $operation->getName());
        $this->assertEquals(FunctionOperation::CREATE, $operation->getOperation());
        $this->assertEquals(self::BODY_TWO, $operation->getBody());
    }

    public function testApplyDropToCreate()
    {
        $base = new FunctionOperation('user_view', FunctionOperation::CREATE, self::BODY_ONE);

        $operation = $base->apply(new FunctionOperation('user_view', FunctionOperation::DROP));

        $this->assertNull($operation);
    }

    public function testApplyAlterToAlter()
    {
        $base = new FunctionOperation('user_view', FunctionOperation::ALTER, self::BODY_ONE);

        $operation = $base->apply(new FunctionOperation('user_view', FunctionOperation::ALTER, self::BODY_TWO));

        $this->assertEquals('user_view', $operation->getName());
        $this->assertEquals(FunctionOperation::ALTER, $operation->getOperation());
        $this->assertEquals(self::BODY_TWO, $operation->getBody());
    }

    public function testApplyDropToAlter()
    {
        $base = new FunctionOperation('user_view', FunctionOperation::ALTER, self::BODY_ONE);

        $drop = new FunctionOperation('user_view', FunctionOperation::DROP);

        $operation = $base->apply($drop);
        $this->assertEquals($drop->getOperation(), $operation->getOperation());
    }

    public function testReverseCreate()
    {
        $base = new FunctionOperation('user_view', FunctionOperation::CREATE, self::BODY_ONE);

        $operation = $base->reverse();

        $this->assertEquals('user_view', $operation->getName());
        $this->assertEquals(FunctionOperation::DROP, $operation->getOperation());
    }

    public function testReverseAlter()
    {
        $base = new FunctionOperation('user_view', FunctionOperation::ALTER, self::BODY_TWO);

        $create = new FunctionOperation('user_view', FunctionOperation::CREATE, self::BODY_ONE);

        $operation = $base->reverse($create);

        $this->assertEquals('user_view', $operation->getName());
        $this->assertEquals(FunctionOperation::ALTER, $operation->getOperation());
        $this->assertEquals(self::BODY_ONE, $operation->getBody());
    }

    public function testReverseDrop()
    {
        $base = new FunctionOperation('user_view', FunctionOperation::DROP);

        $create = new FunctionOperation('user_view', FunctionOperation::CREATE, self::BODY_ONE);

        $operation = $base->reverse($create);

        $this->assertEquals($create, $operation);
    }
}
