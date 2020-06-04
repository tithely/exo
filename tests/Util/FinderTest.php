<?php

namespace Exo\Util;

class FinderTest extends \PHPUnit\Framework\TestCase
{
    public function testFromPath()
    {
        $finder = new Finder();
        $history = $finder->fromPath(__DIR__ . '/../Migrations');
        $operations = $history->play('20190901_create_users', '20200604_create_user_level_function');

        $this->assertEquals([
            '20190901_create_users',
            '20190905_alter_users',
            '20190912_create_posts',
            '20200602_create_user_counts_view',
            '20200604_create_user_level_function'
        ], $history->getVersions());
        $this->assertEquals('users', $operations[0]->getTable());
        $this->assertEquals('users', $operations[1]->getTable());
        $this->assertEquals('posts', $operations[2]->getTable());
        $this->assertEquals('user_counts', $operations[3]->getView());
        $this->assertEquals('user_level', $operations[4]->getName());
    }
}
