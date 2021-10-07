<?php
namespace Exo\Operation;

use PHPUnit\Framework\TestCase;

class ProcedureOperationTest extends TestCase
{
    const BODY_ONE = "SELECT COUNT(*) INTO total FROM posts;";
    const BODY_TWO = "UPDATE users SET email = userEmail WHERE id = uid;";

    public function testCreate()
    {
        $operation = new ProcedureOperation(
            'total_posts_create',
            ProcedureOperation::CREATE,
            false,
            'READS SQL DATA',
            [
                new ParameterOperation('total', ParameterOperation::ADD, ['type' => 'integer'])
            ],
            [],
            self::BODY_ONE
        );

        $this->assertEquals('total_posts_create', $operation->getName());

        $this->assertFalse($operation->getDeterministic());
        $this->assertEquals('READS SQL DATA', $operation->getReadsSqlData());

        $this->assertCount(1, $operation->getInParameterOperations());

        $this->assertEquals('total', $operation->getInParameterOperations()[0]->getName());
        $this->assertEquals(ParameterOperation::ADD, $operation->getInParameterOperations()[0]->getOperation());
        $this->assertEquals(['type' => 'integer'], $operation->getInParameterOperations()[0]->getOptions());

        $this->assertEquals(self::BODY_ONE, $operation->getBody());
    }

    public function testReverseCreate()
    {
        $base = new ProcedureOperation(
            'total_posts_reverse_create',
            ProcedureOperation::CREATE,
            false,
            'READS SQL DATA',
            [
                new ParameterOperation('total', ParameterOperation::ADD, ['type' => 'integer'])
            ],
            [],
            self::BODY_ONE
        );

        $operation = $base->reverse();

        $this->assertEquals('total_posts_reverse_create', $operation->getName());
        $this->assertEquals(ProcedureOperation::DROP, $operation->getOperation());
    }

    public function testDrop()
    {
        $operation = new ProcedureOperation('total_posts_drop', ProcedureOperation::DROP);

        $this->assertEquals('total_posts_drop', $operation->getName());
    }

    public function testReverseDrop()
    {
        $base = new ProcedureOperation('total_posts_reverse_drop', ProcedureOperation::DROP);

        $previous = new ProcedureOperation(
            'total_posts_reverse_drop',
            ProcedureOperation::CREATE,
            false,
            'READS SQL DATA',
            [
                new ParameterOperation('total', ParameterOperation::ADD, ['type' => 'integer'])
            ],
            [],
            self::BODY_ONE
        );

        $operation = $base->reverse($previous);

        $this->assertEquals($previous, $operation);
    }
}
