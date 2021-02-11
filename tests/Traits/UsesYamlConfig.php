<?php

namespace Exo\Tests\Traits;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Exception;

trait UsesYamlConfig
{
    /**
     * @param string|null $keys A top-level key from the parsed yaml array, or
     *                          a dot-notation set of keys, e.g. toplevel.second.third
     * @return array|string|null Depending on the sort of key given, and how
     *                          deeply the full yaml array is plumbed, a string or array may be returned.
     *                          When the value cannot be found by the given key, we default to null.
     * @throws Exception
     */
    protected static function yaml(string $keys = null)
    {
        $yaml = self::readYamlFile();

        if (empty($keys)) {
            return $yaml;
        }

        // parse the keys, and return the config from the appropriate level (following dot-notation)
        foreach (explode('.', $keys) as $segment) {
            if (array_key_exists($segment, $yaml)) {
                $yaml = $yaml[$segment];
            } else {
                return null;
            }
        }

        return $yaml;
    }

    /**
     * Handle validating the existence of the yaml file, parse and returning its contents
     *
     * @return mixed
     * @throws Exception
     */
    private static function readYamlFile(): array
    {
        $path = sprintf('%s/tests/%s', getcwd(), 'db.yml');

        if (!is_readable($path)) {
            throw new Exception('tests/db.yml file does not exist, or cannot be read. See README for instructions');
        }
        try {
            $yaml = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new Exception(
                'Could not parse tests/db.yml file. See README for instructions. YAML parse error: ' . $e->getMessage()
            );
        }

        return $yaml;
    }
}
