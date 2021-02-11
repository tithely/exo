<?php

namespace Exo;

use Exo\Operation\OperationInterface;
use Exo\Operation\ReversibleOperationInterface;
use InvalidArgumentException;

class History
{
    /**
     * @var MigrationInterface[]
     */
    private array $migrations = [];

    /**
     * Returns operations reduced by name.
     *
     * @param OperationInterface[] $operations
     * @return OperationInterface[]
     */
    private static function reduce(array $operations): array
    {
        $reduced = [];

        foreach ($operations as $operation) {

            if (!isset($reduced[$operation->getName()])) {
                $reduced[$operation->getName()] = $operation;
            } else {
                if ($operation->getSupportsReduction()) {
                    $reduced[$operation->getName()] = $reduced[$operation->getName()]->apply($operation);
                } else {
                    throw new InvalidArgumentException(sprintf(
                        'The current operation and type (%s::%s) does not support reduction and has a duplicate name (%s)',
                        get_class($operation),
                        $operation->getOperation(),
                        $operation->getName()
                    ));
                }
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
    public function add(string $version, MigrationInterface $migrationOrView)
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
    public function clone(array $versions = null): History
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
     * @return OperationInterface[]
     */
    public function play(string $from, string $to, bool $reduce = false): array
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
     * @return OperationInterface[]
     */
    public function rewind(string $from, string $to, bool $reduce = false): array
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

                $operation = $migration->getOperation();

                // Reverse the operation if supported
                if ($operation->getSupportsReversal()) {
                    /* @var OperationInterface|ReversibleOperationInterface $operation */
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
    public function getVersions(): array
    {
        return array_map('strval', array_keys($this->migrations));
    }
}
