<?php

namespace Exo;

use Exo\Operation\AbstractOperation;
use Exo\Statement\MysqlStatementBuilder;
use Exo\Statement\StatementBuilder;
use PDO;

class Handler
{
    /**
     * @var PDO
     */
    private $db;

    /**
     * @var History
     */
    private $history;

    /**
     * Handler constructor.
     *
     * @param PDO     $db
     * @param History $history
     */
    public function __construct(PDO $db, History $history)
    {
        $this->db = $db;
        $this->history = $history;
    }

    /**
     * Migrates from current to target version.
     *
     * If $current is a string, all history since the specified
     * version will be executed. If $current is an array, any
     * versions not present will be executed. If $target is a
     * string, history up to and including the specified version
     * will be executed.
     *
     * @param string[]|string|null $current
     * @param string|null          $target
     * @param bool                 $reduce
     * @return HandlerResult[]
     * @throws Operation\UnsupportedOperationException
     */
    public function migrate($current, ?string $target, bool $reduce): array
    {
        $versions = $this->history->getVersions();
        $version = null;

        if (is_array($current)) {
            // Determine versions excluding all executed
            $versions = array_values(array_diff($versions, $current));
            $history = $this->history->clone($versions);
        } else {
            // Determine versions since last executed
            while ($version !== $current && !empty($versions)) {
                $version = array_shift($versions);
            }

            if (!is_null($current) && is_null($version)) {
                throw new \InvalidArgumentException('Current version is invalid.');
            }

            $history = $this->history;
        }

        // Determine range of versions to play
        $from = reset($versions);
        $to = $target ?? end($versions);

        // Execute operations
        $operations = $history->play($from, $to, $reduce);

        return $this->processOperations($operations, $versions, $reduce);
    }

    /**
     * Performs a rollback from current to target version.
     *
     * If $current is an array, any versions not present will
     * not be considered by the rollback.
     *
     * @param string[]|string $current
     * @param string|null     $target
     * @param bool            $reduce
     * @return HandlerResult[]
     * @throws Operation\UnsupportedOperationException
     */
    public function rollback($current, ?string $target, bool $reduce): array
    {
        $versions = $this->history->getVersions();

        if (is_array($current)) {
            // Determine range from executed and target versions
            $versions = $current;
            $history = $this->history->clone($versions);

            $from = $target ? array_search($target, $versions) : 0;
            $count = null;
        } else {
            // Determine range from current and target versions
            $history = $this->history;

            $from = $target ? array_search($target, $versions) : 0;
            $count = array_search($current, $versions) - $from;
        }

        $versions = array_slice(
            $versions,
            $from + 1,
            $count
        );

        $operations = $history->rewind(end($versions), reset($versions), $reduce);
        $versions = array_reverse($versions);

        return $this->processOperations($operations, $versions, $reduce);
    }

    /**
     * @param AbstractOperation[] $operations
     * @param array               $versions
     * @param bool                $reduce
     * @return HandlerResult[]
     * @throws Operation\UnsupportedOperationException
     */
    private function processOperations(array $operations, array $versions, bool $reduce): array
    {
        $results = [];

        foreach ($operations as $offset => $operation) {
            $sql = $this->getBuilder()->build($operation);
            $result = $this->db->exec($sql);

            $results[] = new HandlerResult(
                $reduce ? null : $versions[$offset],
                $result !== false,
                $sql,
                $result === false ? $this->db->errorInfo() : null
            );

            // Stop processing if there was a failure
            if ($result === false) {
                break;
            }
        }

        return $results;
    }

    /**
     * Constructs a statement builder based on the PDO driver.
     *
     * @return StatementBuilder
     */
    private function getBuilder()
    {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

        switch ($driver) {
            case 'mysql':
                return new MysqlStatementBuilder();
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported driver "%s".', $driver));
        }
    }
}
