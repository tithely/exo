<?php

namespace Exo;

use Exo\Operation\ExecOperation;
use Exo\Operation\TableOperation;

class History
{
    /**
     * @var MigrationInterface[]
     */
    private $migrations = [];

    /**
     * Returns operations reduced by table.
     *
     * @param TableOperation[] $operations
     * @return TableOperation[]
     */
    private static function reduce(array $operations): array
    {
        $reduced = [];

        foreach ($operations as $operation) {
            if (!isset($reduced[$operation->getName()])) {
                $reduced[$operation->getName()] = $operation;
            } else {
                $reduced[$operation->getName()] = $reduced[$operation->getName()]->apply($operation);
            }
        }

        return array_values(array_filter(array_values($reduced), function ($operation) {
            return !is_null($operation);
        }));
    }

    /**
     * Adds a migration to the history.
     *
     * @param string             $version
     * @param MigrationInterface $migrationOrView
     */
    public function add(string $version, $migrationOrView)
    {
        $this->migrations[$version] = $migrationOrView;
    }

    /**
     * Clones the history, optionally including only the
     * specified versions.
     *
     * @param array|null $versions
     * @return History
     */
    public function clone(array $versions = null)
    {
        $history = new History();

        foreach ($this->migrations as $version => $migration) {
            if (is_null($versions) || in_array($version, $versions)) {
                $history->add($version, $migration);
            }
        }

        return $history;
    }

    /**
     * Returns an array of operations spanning the supplied version
     * numbers. If reduce is true, operations for the same table
     * will be reduced into a single operation.
     *
     * @param string $from
     * @param string $to
     * @param bool $reduce
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
     * Returns an array of reversed operations spanning the supplied
     * version numbers. If reduce is true, operations for the same
     * table will be reduced into a single operation.
     *
     * @param string $from
     * @param string $to
     * @param bool   $reduce
     * @return TableOperation[]
     */
    public function rewind(string $from, string $to, bool $reduce = false)
    {
        $operations = [];
        $entities = [];
        $inRange = false;

        foreach ($this->migrations as $version => $migration) {
            if (strval($version) === $to) {
                $inRange = true;
            }

            if ($inRange) {
                // Build history to target version
                $history = [];
                $versions = $this->getVersions();
                $versions = array_slice($versions, 0, array_search($version, $versions));

                if (!empty($versions)) {
                    $history = $this->play($versions[0], array_pop($versions), true);
                }

                foreach ($history as $operation) {
                    $entities[$operation->getName()] = $operation;
                }

                // Reverse the operation
                $operation = $migration->getOperation();
                if (!$operation instanceof ExecOperation) {
                    array_unshift($operations, $operation->reverse($entities[$operation->getName()] ?? null));
                }
            }

            if (strval($version) === $from) {
                $inRange = false;
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
