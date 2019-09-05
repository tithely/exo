<?php

namespace Exo\Statement;

use Exo\Operation\ColumnOperation;
use Exo\Operation\TableOperation;

class MysqlStatementBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provider
     */
    public function testBuild(TableOperation $operation, string $sql)
    {
        $handler = new MysqlStatementBuilder();
        $this->assertEquals($sql, $handler->build($operation));
    }

    public function provider()
    {
        return [
            [
                new TableOperation('users', TableOperation::CREATE, [
                    new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid', 'primary' => true]),
                    new ColumnOperation('username', ColumnOperation::ADD, ['type' => 'string', 'length' => 64, 'null' => false]),
                    new ColumnOperation('password', ColumnOperation::ADD, ['type' => 'string'])
                ]),
                'CREATE TABLE `users` (`id` CHAR(36) PRIMARY KEY, `username` VARCHAR(64) NOT NULL, `password` VARCHAR(255));'
            ],
            [
                new TableOperation('users', TableOperation::ALTER, [
                    new ColumnOperation('meta', ColumnOperation::ADD, ['type' => 'json', 'after' => 'password']),
                    new ColumnOperation('username', ColumnOperation::MODIFY, ['type' => 'string', 'length' => 255]),
                    new ColumnOperation('created_at', ColumnOperation::DROP, [])
                ]),
                'ALTER TABLE `users` ADD COLUMN `meta` JSON AFTER `password`, MODIFY COLUMN `username` VARCHAR(255), DROP COLUMN `created_at`;'
            ],
            [
                new TableOperation('users', TableOperation::DROP, []),
                'DROP TABLE `users`;'
            ]
        ];
    }
}
