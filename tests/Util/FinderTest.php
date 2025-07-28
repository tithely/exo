<?php

namespace Exo\Util;

use Exo\Operation\ViewOperation;
use PHPUnit\Framework\TestCase;

class FinderTest extends TestCase
{
    public function testFromPath()
    {
        $finder = new Finder();
        $history = $finder->fromPath(__DIR__ . '/../Migrations');
        $operations = $history->play('20190901_create_users_table', '20211122_create_all_columns_and_types_table');

        $this->assertEquals([
            '20190901_create_users_table',
            '20190905_alter_users_table',
            '20190912_create_posts_table',
            '20200602_create_user_counts_view',
            '20200604_create_user_level_function',
            '20200605_alter_user_counts_view-with_context',
            '20210210_create_user_data_seed',
            '20211007_create_post_count_procedure',
            '20211122_create_all_columns_and_types_table'
        ], $history->getVersions());
        $this->assertEquals('users', $operations[0]->getName());
        $this->assertEquals('users', $operations[1]->getName());
        $this->assertEquals('posts', $operations[2]->getName());
        $this->assertEquals('user_counts', $operations[3]->getName());
        $this->assertEquals('user_level', $operations[4]->getName());
        $this->assertEquals('user_counts', $operations[5]->getName());
        $this->assertEquals('user_data_seed', $operations[6]->getName());
        $this->assertEquals('post_count', $operations[7]->getName());
        $this->assertEquals('all_columns_and_types', $operations[8]->getName());
    }

    public function testFromPathWithContextHappyPath()
    {
        $finder = new Finder([
            'tenant_database_name' => 'test'
        ]);
        $history = $finder->fromPath(__DIR__ . '/../Migrations');
        $operations = $history->play('20200605_alter_user_counts_view-with_context', '20200605_alter_user_counts_view-with_context');

        /* @var ViewOperation $operation */
        $operation = $operations[0];

        $this->assertEquals('user_counts', $operation->getName());
        $this->assertEquals('alter', $operation->getOperation());

        $this->assertEquals(
            'SELECT COUNT(id) AS user_count FROM test.users',
            $operation->getBody()
        );
    }

    public function testFromPathWithContextSadPath()
    {
        $finder = new Finder([]);
        $history = $finder->fromPath(__DIR__ . '/../Migrations');
        $operations = $history->play('20200605_alter_user_counts_view-with_context', '20200605_alter_user_counts_view-with_context');

        /* @var ViewOperation $operation */
        $operation = $operations[0];

        $this->assertEquals('user_counts', $operation->getName());
        $this->assertEquals('alter', $operation->getOperation());

        $this->assertEquals(
            'SELECT COUNT(id) AS user_count FROM undefined.users',
            $operation->getBody()
        );
    }
}
