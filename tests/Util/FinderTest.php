<?php

namespace Exo\Util;

use Exo\Operation\ViewOperation;

class FinderTest extends \PHPUnit\Framework\TestCase
{
    public function testFromPath()
    {
        $finder = new Finder();
        $history = $finder->fromPath(__DIR__ . '/../Migrations');
        $operations = $history->play('20190901_create_users', '20210210_create_user_data_seed');

        $this->assertEquals([
            '20190901_create_users',
            '20190905_alter_users',
            '20190912_create_posts',
            '20200602_create_user_counts_view',
            '20200604_create_user_level_function',
            '20200605_create_user_counts_view_with_context',
            '20210210_create_user_data_seed'
        ], $history->getVersions());
        $this->assertEquals('users', $operations[0]->getName());
        $this->assertEquals('users', $operations[1]->getName());
        $this->assertEquals('posts', $operations[2]->getName());
        $this->assertEquals('user_counts', $operations[3]->getName());
        $this->assertEquals('user_level', $operations[4]->getName());
        $this->assertEquals('user_counts', $operations[5]->getName());
        $this->assertEquals('user_data_seed', $operations[6]->getName());
    }

    public function testFromPathWithContextHappyPath()
    {
        $finder = new Finder([
            'tenant_database_name' => 'contextual_database_name'
        ]);
        $history = $finder->fromPath(__DIR__ . '/../Migrations');
        $operations = $history->play('20200605_create_user_counts_view_with_context', '20200605_create_user_counts_view_with_context');

        /* @var ViewOperation $operation */
        $operation = $operations[0];

        $this->assertEquals('user_counts', $operation->getName());
        $this->assertEquals('alter', $operation->getOperation());

        $this->assertEquals(
            'select count(distinct id) as user_count from `contextual_database_name`.`users`',
            $operation->getBody()
        );
    }

    public function testFromPathWithContextSadPath()
    {
        $finder = new Finder([]);
        $history = $finder->fromPath(__DIR__ . '/../Migrations');
        $operations = $history->play('20200605_create_user_counts_view_with_context', '20200605_create_user_counts_view_with_context');

        /* @var ViewOperation $operation */
        $operation = $operations[0];

        $this->assertEquals('user_counts', $operation->getName());
        $this->assertEquals('alter', $operation->getOperation());

        $this->assertEquals(
            'select count(distinct id) as user_count from `undefined`.`users`',
            $operation->getBody()
        );
    }
}
