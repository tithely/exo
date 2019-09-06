<?php

namespace Exo;

use Exo\Operation\TableOperation;

class History
{
    /**
     * @var array
     */
    private $migrations = [];

    /**
     * Returns operations reduced by table.
     *
     * @param TableOperation[] $operations
     * @return TableOperation[]
     */
    private static function reduce(array $operations)
    {
        $reduced = [];

        foreach ($operations as $operation) {
            if (!isset($reduced[$operation->getTable()])) {
                $reduced[$operation->getTable()] = $operation;
            } else {
                $reduced[$operation->getTable()] = $reduced[$operation->getTable()]->apply($operation);
            }
        }

        return array_filter(array_values($reduced), function ($operation) {
            return !is_null($operation);
        });
    }

    /**
     * Adds a migration to the history.
     *
     * @param string    $version
     * @param Migration $migration
     */
    public function add(string $version, Migration $migration)
    {
        $this->migrations[$version] = $migration;
    }

    /**
     * Returns an array of operations spanning the supplied version
     * numbers. If reduce is true, operations for the same table
     * will be reduced into a single operation.
     *
     * @param string $from
     * @param string $to
     * @param bool   $reduce
     * @return TableOperation[]
     */
    public function play(string $from, string $to, bool $reduce = false)
    {
        $operations = [];
        $inRange = false;

        foreach ($this->migrations as $version => $migration) {
            if (strval($version) === $from) {
                $inRange = true;
            }

            if ($inRange) {
                $operations[] = $migration->getOperation();
            }

            if (strval($version) === $to) {
                break;
            }
        }

        if ($reduce) {
            return self::reduce($operations);
        }

        return $operations;
    }

    /**
     * Returns an ordered array of versions present in the history.
     *
     * @return string[]
     */
    public function getVersions()
    {
        return array_map('strval', array_keys($this->migrations));
    }
}
