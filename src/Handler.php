<?php

namespace Exo;

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
     * @param PDO    $db
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
     * @param string|null $current
     * @param string|null $target
     * @param bool $reduce
     * @return HandlerResult[]
     * @throws Operation\UnsupportedOperationException
     */
    public function migrate(?string $current, ?string $target, bool $reduce)
    {
        $versions = $this->history->getVersions();
        $version = null;

        while ($version !== $current && !empty($versions)) {
            $version = array_shift($versions);
        }

        if (!is_null($current) && is_null($version)) {
            throw new \InvalidArgumentException('Current version is invalid.');
        }

        // Determine range of versions to play
        $from = reset($versions);
        $to = $target ?? end($versions);

        // Execute operations
        $operations = $this->history->play($from, $to, $reduce);
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

            // Stop migration if there was a failure
            if ($result === false) {
                break;
            }
        }

        return $results;
    }

    /**
     * Performs a rollback from current to target version.
     *
     * @param string $current
     * @param string|null $target
     * @param bool $reduce
     * @return HandlerResult[]
     * @throws Operation\UnsupportedOperationException
     */
    public function rollback(string $current, ?string $target, bool $reduce)
    {
        $versions = $this->history->getVersions();
        $from = $target ? array_search($target, $versions) : 0;
        $count = array_search($current, $versions) - $from;

        $versions = array_slice(
            $versions,
            $from + 1,
            $count
        );

        $operations = $this->history->rewind(end($versions), reset($versions), $reduce);
        $results = [];

        $versions = array_reverse($versions);

        foreach ($operations as $offset => $operation) {
            $sql = $this->getBuilder()->build($operation);
            $result = $this->db->exec($sql);

            $results[] = new HandlerResult(
                $reduce ? null : $versions[$offset],
                $result !== false,
                $sql,
                $result === false ? $this->db->errorInfo() : null
            );

            // Stop rollback if there was a failure
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
