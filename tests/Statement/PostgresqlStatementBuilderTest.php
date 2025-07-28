<?php

namespace Exo\Statement;

use Exo\Operation\ColumnOperation;
use Exo\Operation\FunctionOperation;
use Exo\Operation\IndexOperation;
use Exo\Operation\ParameterOperation;
use Exo\Operation\ProcedureOperation;
use Exo\Operation\ReturnTypeOperation;
use Exo\Operation\TableOperation;
use Exo\Operation\VariableOperation;
use Exo\Operation\ViewOperation;
use InvalidArgumentException;

class PostgresqlStatementBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provider
     * @param TableOperation|ViewOperation $operation
     * @param string $sql
     */
    public function testBuild($operation, string $sql)
    {
        $handler = new PostgresqlStatementBuilder();
        $this->assertEquals($sql, trim($handler->build($operation)));
    }

    public function provider()
    {
        return [
            [
                new TableOperation('users', TableOperation::CREATE, [
                    new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid', 'primary' => true]),
                    new ColumnOperation('username', ColumnOperation::ADD, ['type' => 'string', 'length' => 64, 'null' => false]),
                    new ColumnOperation('password', ColumnOperation::ADD, ['type' => 'string']),
                    new ColumnOperation('tinytext', ColumnOperation::ADD, ['type' => 'text', 'length' => 255]),
                    new ColumnOperation('summary', ColumnOperation::ADD, ['type' => 'text']),
                    new ColumnOperation('description', ColumnOperation::ADD, ['type' => 'text', 'length' => 16777215]),
                    new ColumnOperation('novel', ColumnOperation::ADD, ['type' => 'text', 'length' => 4294967295]),
                    new ColumnOperation('archived', ColumnOperation::ADD, ['type' => 'bool', 'default' => 1]),
                    new ColumnOperation('status', ColumnOperation::ADD, ['type' => 'string', 'default' => 'draft']),
                    new ColumnOperation('created_at', ColumnOperation::ADD, ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ], [
                    new IndexOperation('username', IndexOperation::ADD, ['username'], ['unique' => true])
                ]),
                'CREATE TABLE "users" ("id" CHAR(36) PRIMARY KEY, "username" VARCHAR(64) NOT NULL, ' .
                '"password" VARCHAR(255), ' .
                '"tinytext" TEXT, "summary" TEXT, "description" TEXT, "novel" TEXT, ' .
                '"archived" SMALLINT DEFAULT 1, "status" VARCHAR(255) DEFAULT \'draft\', ' .
                '"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP); CREATE UNIQUE INDEX "id_users_idx" ON "users" ("id"); CREATE UNIQUE INDEX "username_users_idx" ON "users" ("username");'
            ],
            [
                new TableOperation('users', TableOperation::CREATE, [
                    new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid', 'primary' => true]),
                    new ColumnOperation('created_at', ColumnOperation::ADD, ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP'])
                ], []),
                'CREATE TABLE "users" ("id" CHAR(36) PRIMARY KEY, "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP); CREATE UNIQUE INDEX "id_users_idx" ON "users" ("id");'
            ],
            [
                new TableOperation('users', TableOperation::CREATE, [
                    new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid', 'primary' => true]),
                    new ColumnOperation('created_at', ColumnOperation::ADD, ['type' => 'timestamp', 'update' => 'CURRENT_TIMESTAMP'])
                ], []),
                'CREATE TABLE "users" ("id" CHAR(36) PRIMARY KEY, "created_at" TIMESTAMP ON UPDATE CURRENT_TIMESTAMP); CREATE UNIQUE INDEX "id_users_idx" ON "users" ("id");'
            ],
            [
                new TableOperation('users', TableOperation::CREATE, [
                    new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid', 'primary' => true]),
                    new ColumnOperation('created_at', ColumnOperation::ADD, ['type' => 'timestamp', 'null' => true])
                ], []),
                'CREATE TABLE "users" ("id" CHAR(36) PRIMARY KEY, "created_at" TIMESTAMP NULL); CREATE UNIQUE INDEX "id_users_idx" ON "users" ("id");'
            ],
            [
                new TableOperation('users', TableOperation::CREATE, [
                    new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid', 'primary' => true]),
                    new ColumnOperation('created_at', ColumnOperation::ADD, ['type' => 'timestamp', 'null' => false])
                ], []),
                'CREATE TABLE "users" ("id" CHAR(36) PRIMARY KEY, "created_at" TIMESTAMP NOT NULL); CREATE UNIQUE INDEX "id_users_idx" ON "users" ("id");'
            ],
            [
                new TableOperation('users', TableOperation::CREATE, [
                    new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid', 'primary' => true]),
                    new ColumnOperation('created_at', ColumnOperation::ADD, ['type' => 'timestamp'])
                ], []),
                'CREATE TABLE "users" ("id" CHAR(36) PRIMARY KEY, "created_at" TIMESTAMP); CREATE UNIQUE INDEX "id_users_idx" ON "users" ("id");'
            ],
            [
                new TableOperation('users', TableOperation::ALTER, [
                    new ColumnOperation('meta', ColumnOperation::ADD, ['type' => 'json', 'default' => '["meta"]', 'after' => 'password']),
                    new ColumnOperation('date', ColumnOperation::ADD, ['type' => 'timestamp', 'update' => 'CURRENT_TIMESTAMP', 'after' => 'meta']),
                    new ColumnOperation('username', ColumnOperation::MODIFY, ['type' => 'string', 'length' => 255]),
                    new ColumnOperation('created_at', ColumnOperation::DROP, [])
                ], [
                    new IndexOperation('meta', IndexOperation::ADD, ['meta'], ['unique' => true]),
                    new IndexOperation('username', IndexOperation::DROP, [], [])
                ]),
                'ALTER TABLE "users" ADD COLUMN "meta" JSON DEFAULT \'["meta"]\', ' .
                'ADD COLUMN "date" TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, ' .
                'ALTER COLUMN "username" TYPE VARCHAR(255), ' .
                'DROP COLUMN "created_at" CASCADE; CREATE UNIQUE INDEX "meta_users_idx" ON "users" ("meta"); DROP INDEX "username_users_idx";'
            ],
            [
                new TableOperation('users', TableOperation::DROP, [], []),
                'DROP TABLE "users";'
            ],
            [
                new ViewOperation('user_counts', ViewOperation::CREATE, 'select count(users.id) as user_count from test.users'),
                'CREATE OR REPLACE VIEW "user_counts" AS select count(users.id) as user_count from test.users;'
            ],
            [
                new ViewOperation('user_counts', ViewOperation::ALTER, 'select count(*) as user_count from test.users'),
                'CREATE OR REPLACE VIEW "user_counts" AS select count(*) as user_count from test.users;'
            ],
            [
                new ViewOperation('user_counts', ViewOperation::DROP),
                'DROP VIEW "user_counts";'
            ],
            [
                new FunctionOperation(
                    'user_defined_function',
                    FunctionOperation::CREATE,
                    new ReturnTypeOperation('string', ReturnTypeOperation::ADD, ['length' => 20]),
                    true,
                    'NO SQL',
                    'plpgsql',
                    [
                        new ParameterOperation('inputValue1', ParameterOperation::ADD, ['length' => 32]),
                        new ParameterOperation('inputValue2', ParameterOperation::ADD, ['length' => 32])
                    ],
                    [
                        new VariableOperation('internalVarName1', VariableOperation::ADD, ['type' => 'integer']),
                        new VariableOperation('internalVarName2', VariableOperation::ADD, ['type' => 'integer'])
                    ],
                    'RETURN \'foo\';'
                ),
                sprintf(
                    PostgresqlStatementBuilder::FUNCTION_CREATE,
                    '"user_defined_function"',
                    'inputValue1 VARCHAR(32),inputValue2 VARCHAR(32)',
                    'VARCHAR(20)',
                    'plpgsql',
                    "internalVarName1 INTEGER;\ninternalVarName2 INTEGER;",
                    'RETURN \'foo\';'
                )
            ],
            [
                new FunctionOperation(
                    'user_defined_function',
                    FunctionOperation::REPLACE,
                    new ReturnTypeOperation('string', ReturnTypeOperation::ADD, ['length' => 20]),
                    false,
                    'READS SQL DATA',
                    'plpgsql',
                    [new ParameterOperation('anotherInputValue', ParameterOperation::ADD, ['length' => 32])],
                    [new VariableOperation('anotherVarName', VariableOperation::ADD, ['type' => 'integer'])],
                    'RETURN \'foo\';'
                ),
                sprintf(
                    PostgresqlStatementBuilder::FUNCTION_CREATE,
                    '"user_defined_function"',
                    'anotherInputValue VARCHAR(32)',
                    'VARCHAR(20)',
                    'plpgsql',
                    'anotherVarName INTEGER;',
                    'RETURN \'foo\';'
                )
            ],
            [
                new FunctionOperation(
                    'user_defined_function',
                    FunctionOperation::DROP
                ),
                'DROP FUNCTION "user_defined_function";'
            ],
            [
                new ProcedureOperation(
                    'user_defined_procedure',
                    ProcedureOperation::CREATE,
                    true,
                    'CONTAINS SQL',
                    'plpgsql',
                    [new ParameterOperation('inValue', ParameterOperation::ADD, ['type' => 'integer'])],
                    [new ParameterOperation('outValue', ParameterOperation::ADD, ['type' => 'integer'])],
                    'SELECT inValue INTO outValue;'
                ),
                sprintf(
                    PostgresqlStatementBuilder::PROCEDURE_CREATE,
                    '"user_defined_procedure"',
                    'IN inValue INTEGER',
                    ', ',
                    'OUT outValue INTEGER',
                    'plpgsql',
                    'SELECT inValue INTO outValue;'
                )
            ],
            [
                new ProcedureOperation(
                    'user_defined_procedure',
                    ProcedureOperation::CREATE,
                    false,
                    'READS SQL DATA',
                    'plpgsql',
                    [],
                    [],
                    'SELECT \'Number of Users:\', COUNT(*) FROM test.users;'
                ),
                sprintf(
                    PostgresqlStatementBuilder::PROCEDURE_CREATE,
                    '"user_defined_procedure"',
                    '',
                    '',
                    '',
                    'plpgsql',
                    'SELECT \'Number of Users:\', COUNT(*) FROM test.users;'
                )
            ],
            [
                new ProcedureOperation(
                    'user_defined_procedure',
                    ProcedureOperation::CREATE,
                    false,
                    'READS SQL DATA',
                    'plpgsql',
                    [],
                    [new ParameterOperation('total', ParameterOperation::ADD, ['type' => 'integer'])],
                    'SELECT COUNT(*) INTO total FROM test.users;'
                ),
                sprintf(
                    PostgresqlStatementBuilder::PROCEDURE_CREATE,
                    '"user_defined_procedure"',
                    '',
                    '',
                    'OUT total INTEGER',
                    'plpgsql',
                    'SELECT COUNT(*) INTO total FROM test.users;'
                )
            ],
            [
                new ProcedureOperation(
                    'user_defined_procedure',
                    ProcedureOperation::DROP
                ),
                'DROP PROCEDURE "user_defined_procedure";'
            ]
        ];
    }

    public function testInvalidTextLengthThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid length provided for \'text\' column type.');

        $operation = new TableOperation('users', TableOperation::CREATE, [
            new ColumnOperation('novel', ColumnOperation::ADD, ['type' => 'text', 'length' => 99999999999])
        ], []);

        $handler = new MysqlStatementBuilder();
        $handler->build($operation);
    }
}
