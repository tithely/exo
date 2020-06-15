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
     * Executes pending migrations up to target version.
     *
     * @param string[]    $executed
     * @param string|null $target
     * @param bool        $reduce
     * @return HandlerResult[]
     * @throws Operation\UnsupportedOperationException
     */
    public function migrate(array $executed, ?string $target, bool $reduce): array
    {
        $versions = $this->history->getVersions();

        // Determine versions excluding all executed
        $versions = array_values(array_diff($versions, $executed));
        $history = $this->history->clone($versions);

        // Determine range of versions to play
        $from = reset($versions);
        $to = $target ?? end($versions);

        // Execute operations
        $operations = $history->play($from, $to, $reduce);

        return $this->processOperations($operations, $versions, $reduce);
    }

    /**
     * Performs a rollback to target version.
     *
     * @param string[] $executed
     * @param string   $target
     * @param bool     $reduce
     * @return HandlerResult[]
     * @throws Operation\UnsupportedOperationException
     */
    public function rollback(array $executed, string $target, bool $reduce): array
    {
        $history = $this->history->clone($executed);
        $from = array_search($target, $executed);

        if ($from === false) {
            throw new \InvalidArgumentException(sprintf('Unknown target version "%s".', $target));
        }

        $versions = array_slice(
            $executed,
            $from + 1
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
