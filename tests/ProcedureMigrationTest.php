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
            'withInParameter' => ['name' => 'inParameter', 'param' => ['type' => 'integer'], 'message' => 'Cannot add parameters in a procedure drop migration.'],
            'withOutParameter' => ['name' => 'outParameter', 'param' => ['type' => 'integer'], 'message' => 'Cannot add parameters in a procedure drop migration.'],
            'withDeterminism' => ['param' => false, 'message' => 'Cannot set deterministic property in a procedure drop migration.'],
            'withDataUse' => ['param' => 'CONTAINS SQL', 'message' => 'Cannot set dataUse property in a procedure drop migration.'],
            'withLanguage' => ['param' => 'SQL', 'message' => 'Cannot set language property in a procedure drop migration.']
        ];
    }

    protected function tearDown(): void
    {
        $this->methods = null;
    }

    private function callMigrationMethod(string $method, string $operation): ProcedureOperation
    {
        if ($operation === ProcedureOperation::CREATE) {
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
            case 'withInParameter':
                $migration->withInParameter($name, $param);
                $this->assertEquals($name, $migration->getOperation()->getInParameterOperations()[0]->getName());
                $this->assertEquals($param, $migration->getOperation()->getInParameterOperations()[0]->getOptions());
                break;
            case 'withOutParameter':
                $migration->withOutParameter($name, $param);
                $this->assertEquals($name, $migration->getOperation()->getOutParameterOperations()[0]->getName());
                $this->assertEquals($param, $migration->getOperation()->getOutParameterOperations()[0]->getOptions());
                break;
            case 'withDeterminism':
                $migration->withDeterminism($param);
                $this->assertEquals($param, $migration->getOperation()->getDeterminism());
                break;
            case 'withLanguage':
                $migration->withLanguage($param);
                $this->assertEquals($param, $migration->getOperation()->getLanguage());
                break;
            case 'withDataUse':
                $migration->withDataUse($param);
                $this->assertEquals($param, $migration->getOperation()->getDataUse());
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
