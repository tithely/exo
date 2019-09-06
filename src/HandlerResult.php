<?php

namespace Exo;

class HandlerResult
{
    /**
     * @var string|null
     */
    private $version;

    /**
     * @var bool
     */
    private $success;

    /**
     * @var string
     */
    private $sql;

    /**
     * @var array|null
     */
    private $errorInfo;

    /**
     * HandlerResult constructor.
     *
     * @param string|null $version
     * @param bool        $success
     * @param string      $sql
     * @param array|null  $errorInfo
     */
    public function __construct(?string $version, bool $success, string $sql, ?array $errorInfo)
    {
        $this->version = $version;
        $this->success = $success;
        $this->sql = $sql;
        $this->errorInfo = $errorInfo;
    }

    /**
     * Returns the associated migration version.
     *
     * @return string|null
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Returns true if the statement ran successfully.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Returns the SQL statement that was executed.
     *
     * @return string
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * Returns error info from the database handle.
     *
     * @return array|null
     */
    public function getErrorInfo(): ?array
    {
        return $this->errorInfo;
    }
}
