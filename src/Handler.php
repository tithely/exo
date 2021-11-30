<?php

namespace Exo;

use Exo\Operation\ExecOperation;
use Exo\Operation\OperationInterface;
use Exo\Statement\MysqlStatementBuilder;
use Exo\Statement\PostgresqlStatementBuilder;
use Exo\Statement\StatementBuilder;
use InvalidArgumentException;
use PDO;

class Handler
{
    /**
     * @var PDO
     */
    private PDO $db;

    /**
     * @var History
     */
    private History $history;

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
     * @param string[]    $executed
     * @param string|null $target
     * @param bool        $reduce
     * @return HandlerResult[]
     * @throws Operation\UnsupportedOperationException
     */
    public function rollback(array $executed, ?string $target, bool $reduce): array
    {
        $history = $this->history->clone($executed);
        $from = -1;

        if ($target) {
            $from = array_search($target, $executed);

            if ($from === false) {
                throw new InvalidArgumentException(sprintf('Unknown target version "%s".', $target));
            }
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
     * @param OperationInterface[] $operations
     * @param array               $versions
     * @param bool                $reduce
     * @return HandlerResult[]
     * @throws Operation\UnsupportedOperationException
     */
    private function processOperations(array $operations, array $versions, bool $reduce): array
    {
        $results = [];

        // Set connection error mode
        $errorMode = $this->db->getAttribute(PDO::ATTR_ERRMODE);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

        foreach ($operations as $offset => $operation) {
            $sql = $this->getBuilder()->build($operation);

            // Determine if transaction is required
            $transactionRequired = $operation instanceof ExecOperation;

            // Begin transaction if needed
            if ($transactionRequired) {
                $this->db->beginTransaction();
            }

            // Process operation
            $result = $this->db->exec($sql);

            // Get results
            $results[] = new HandlerResult(
                $reduce ? null : $versions[$offset],
                $result !== false,
                $sql,
                $result === false ? $this->db->errorInfo() : null
            );

            // Check for failure
            if ($result === false) {
                // On Failure:

                // Rollback transaction if started
                if ($transactionRequired) {
                    $this->db->rollBack();
                }

                // Stop processing
                break;

            } else {

                // Commit transaction on success
                if ($transactionRequired) {
                    $this->db->commit();
                }
            }
        }

        // Restore connection error mode
        $this->db->setAttribute(PDO::ATTR_ERRMODE, $errorMode);

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
            case 'pgsql':
                return new PostgresqlStatementBuilder();
            default:
                throw new InvalidArgumentException(sprintf('Unsupported driver "%s".', $driver));
        }
    }
}
