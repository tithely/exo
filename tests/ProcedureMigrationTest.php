<?php

namespace Exo;

use Exo\Operation\ProcedureOperation;
use PHPUnit\Framework\TestCase;
use LogicException;

class ProcedureMigrationTest extends TestCase
{
    const BODY_ONE = "SELECT COUNT(*) INTO total FROM posts;";

    private ?array $methods = [];
    private string $procedureName = 'my_procedure';

    protected function setUp(): void
    {
        $this->methods = [
            'withBody' => ['param' => self::BODY_ONE, 'message' => 'Procedure body can only be set in a procedure create migration.'],
            'addInParameter' => ['name' => 'inParameter', 'param' => ['type' => 'integer'], 'message' => 'Cannot add parameters in a procedure drop migration.'],
            'addOutParameter' => ['name' => 'outParameter', 'param' => ['type' => 'integer'], 'message' => 'Cannot add parameters in a procedure drop migration.'],
            'isDeterministic' => ['param' => false, 'message' => 'Cannot set deterministic property in a procedure drop migration.'],
            'readsSqlData' => ['param' => 'CONTAINS SQL', 'message' => 'Cannot set readsSqlData property in a procedure drop migration.']
        ];
    }

    protected function tearDown(): void
    {
        $this->methods = null;
    }

    private function callMigrationMethod(string $method, string $action): ProcedureOperation
    {
        if ($action === ProcedureOperation::CREATE) {
            $migration = ProcedureMigration::create($this->procedureName);
        } else {
            $migration = ProcedureMigration::drop($this->procedureName);
        }
        $param = $this->methods[$method]['param'] ?? '';
        $name = $this->methods[$method]['name'] ?? '';
        switch ($method) {
            case 'withBody';
                $migration->withBody($param);
                $this->assertEquals($param, $migration->getOperation()->getBody());
                break;
            case 'addInParameter':
                $migration->addInParameter($name, $param);
                $this->assertEquals($name, $migration->getOperation()->getInParameterOperations()[0]->getName());
                $this->assertEquals($param, $migration->getOperation()->getInParameterOperations()[0]->getOptions());
                break;
            case 'addOutParameter':
                $migration->addOutParameter($name, $param);
                $this->assertEquals($name, $migration->getOperation()->getOutParameterOperations()[0]->getName());
                $this->assertEquals($param, $migration->getOperation()->getOutParameterOperations()[0]->getOptions());
                break;
            case 'isDeterministic':
                $migration->isDeterministic($param);
                $this->assertEquals($param, $migration->getOperation()->getDeterministic());
                break;
            case 'readsSqlData':
                $migration->readsSqlData($param);
                $this->assertEquals($param, $migration->getOperation()->getReadsSqlData());
                break;
        }
        return $migration->getOperation();
    }

    private function expectExceptionModifyingDuringDrop(string $method): void
    {
        try {
            $this->callMigrationMethod($method, ProcedureOperation::DROP);
            $this->assertTrue(false);
        } catch (\Exception $exception) {
            $this->assertInstanceOf(LogicException::class, $exception);
            $this->assertEquals($this->methods[$method]['message'], $exception->getMessage());
        }
    }

    public function testPreventModifyDuringDrop(): void
    {
        foreach ($this->methods as $method => $options) {
            $this->expectExceptionModifyingDuringDrop($method);
        }
    }

    public function testCreate(): void
    {
        foreach ($this->methods as $method => $options) {
           $this->callMigrationMethod($method, ProcedureOperation::CREATE);
        }
    }

    public function testDrop(): void
    {
        $operation = $this->callMigrationMethod('', ProcedureOperation::DROP);

        $this->assertEquals($this->procedureName, $operation->getName());
        $this->assertEquals(ProcedureOperation::DROP, $operation->getOperation());
        $this->assertNull($operation->getBody());
    }
}
