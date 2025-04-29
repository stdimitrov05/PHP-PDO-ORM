<?php

namespace Stan\Orm\Interfaces;

use Exception;
use PDO;

interface Database
{
    /**
     * Retrieves the read-only database connection.
     *
     * @return PDO|null The read-only PDO connection or null if not available.
     */
    public function getReadConnection(): ?PDO;

    /**
     * Retrieves the read-write database connection.
     *
     * @return PDO|null The read-write PDO connection or null if not available.
     */
    public function getWriteConnection(): ?PDO;

    /**
     * Fetches a single record from the database.
     *
     * @param string $sql The SQL query to execute.
     * @param array|null $params The parameters to bind to the query.
     * @return object|null The fetched record as an object, or null if no record is found.
     * @throws Exception If the SQL statement preparation or execution fails.
     */
    public function fetchOne(string $sql, ?array $params = null): ?object;

    /**
     * Fetches all records from the database.
     *
     * @param string $sql The SQL query to execute.
     * @param array|null $params The parameters to bind to the query.
     * @return array|null The fetched records as an array of objects.
     * @throws Exception If the SQL statement preparation or execution fails.
     */
    public function fetchAll(string $sql, ?array $params = null, ?int $fetchMode = PDO::FETCH_ASSOC): ?array;

    /**
     * Enables debug mode for check current SQL query.
     *
     * @return Database
     */
    public function debug(): Database;

    /**
     * Executes a SQL statement and returns the result.
     *
     * @param string $sql The SQL query to execute.
     * @param array|null $params The parameters to bind to the query.
     * @return mixed The result of the executed query.
     * @throws Exception
     */
    public function execute(string $sql, ?array $params = null): bool;
}