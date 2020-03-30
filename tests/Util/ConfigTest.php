<?php

namespace Exo\Util;

use Exo\Tests\Traits\UsesYamlConfig;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    use UsesYamlConfig;

    /**
     * @throws
     */
    public function testYamlExists()
    {
        $yaml = self::yaml();

        // check that the file exists
        $this->assertIsArray($yaml);
        // check that at least the top-level handlers array is present
        $this->assertArrayHasKey('handlers', $yaml);
        // check that at least one handler is present
        $this->assertNotTrue(empty($yaml['handlers']));

        // check dot-notation access
        $dotKey = sprintf(
            'handlers.%s',
            current(array_keys($yaml['handlers']))
        );
        $this->assertNotEmpty(self::yaml($dotKey));
    }
}
