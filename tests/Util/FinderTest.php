<?php

namespace Exo\Util;

class FinderTest extends \PHPUnit\Framework\TestCase
{
    public function testFromPath()
    {
        $finder = new Finder();
        $history = $finder->fromPath(__DIR__ . '/../Migrations');
        $operations = $history->play('20190901_create_users', '20190912_create_posts');

        $this->assertEquals(['20190901_create_users', '20190905_alter_users', '20190912_create_posts'], $history->getVersions());
        $this->assertEquals('users', $operations[0]->getTable());
        $this->assertEquals('users', $operations[1]->getTable());
        $this->assertEquals('posts', $operations[2]->getTable());
    }
}
