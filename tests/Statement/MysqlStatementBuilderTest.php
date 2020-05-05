<?php

namespace Exo\Statement;

use Exo\Operation\ColumnOperation;
use Exo\Operation\IndexOperation;
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
                    new ColumnOperation('password', ColumnOperation::ADD, ['type' => 'string']),
                    new ColumnOperation('gender', ColumnOperation::ADD, ['type' => 'enum', 'values' => ['male', 'female'], 'default' => 'male']),
                    new ColumnOperation('created_at', ColumnOperation::ADD, ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ], [
                    new IndexOperation('username', IndexOperation::ADD, ['username'], ['unique' => true])
                ]),
                'CREATE TABLE `users` (`id` CHAR(36) PRIMARY KEY, `username` VARCHAR(64) NOT NULL, ' .
                '`password` VARCHAR(255), `gender` ENUM(\'male\',\'female\') DEFAULT \'male\', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX `username` (`username`) UNIQUE);'
            ],
            [
                new TableOperation('users', TableOperation::CREATE, [
                    new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid', 'primary' => true]),
                    new ColumnOperation('created_at', ColumnOperation::ADD, ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP'])
                ], []),
                'CREATE TABLE `users` (`id` CHAR(36) PRIMARY KEY, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP);'
            ],
            [
                new TableOperation('users', TableOperation::CREATE, [
                    new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid', 'primary' => true]),
                    new ColumnOperation('created_at', ColumnOperation::ADD, ['type' => 'timestamp', 'update' => 'CURRENT_TIMESTAMP'])
                ], []),
                'CREATE TABLE `users` (`id` CHAR(36) PRIMARY KEY, `created_at` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP);'
            ],
            [
                new TableOperation('users', TableOperation::CREATE, [
                    new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid', 'primary' => true]),
                    new ColumnOperation('created_at', ColumnOperation::ADD, ['type' => 'timestamp', 'null' => true])
                ], []),
                'CREATE TABLE `users` (`id` CHAR(36) PRIMARY KEY, `created_at` TIMESTAMP NULL);'
            ],
            [
                new TableOperation('users', TableOperation::CREATE, [
                    new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid', 'primary' => true]),
                    new ColumnOperation('created_at', ColumnOperation::ADD, ['type' => 'timestamp', 'null' => false])
                ], []),
                'CREATE TABLE `users` (`id` CHAR(36) PRIMARY KEY, `created_at` TIMESTAMP NOT NULL);'
            ],
            [
                new TableOperation('users', TableOperation::CREATE, [
                    new ColumnOperation('id', ColumnOperation::ADD, ['type' => 'uuid', 'primary' => true]),
                    new ColumnOperation('created_at', ColumnOperation::ADD, ['type' => 'timestamp'])
                ], []),
                'CREATE TABLE `users` (`id` CHAR(36) PRIMARY KEY, `created_at` TIMESTAMP);'
            ],
            [
                new TableOperation('users', TableOperation::ALTER, [
                    new ColumnOperation('meta', ColumnOperation::ADD, ['type' => 'json', 'after' => 'password']),
                    new ColumnOperation('username', ColumnOperation::MODIFY, ['type' => 'string', 'length' => 255]),
                    new ColumnOperation('created_at', ColumnOperation::DROP, [])
                ], [
                    new IndexOperation('meta', IndexOperation::ADD, ['meta'], []),
                    new IndexOperation('username', IndexOperation::DROP, [], [])
                ]),
                'ALTER TABLE `users` ADD COLUMN `meta` JSON AFTER `password`, MODIFY COLUMN `username` VARCHAR(255), ' .
                'DROP COLUMN `created_at`, ADD INDEX `meta` (`meta`), DROP INDEX `username`;'
            ],
            [
                new TableOperation('users', TableOperation::DROP, [], []),
                'DROP TABLE `users`;'
            ]
        ];
    }
}
