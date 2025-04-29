<?php

namespace Stdimitrov\Orm;

use Exception;
use PDO;
use PDOStatement;
use ReflectionClass;
use Stdimitrov\Orm\Tools\Helper;

class Database implements Interfaces\Database
{
    private bool $forceReadWrite = false;
    private bool $debug = false;
    private PDO $currentInstance;

    private PDO|null $pdoRO = null;

    private PDO|null $pdoRW = null;

    private ?string $sql = '' {
        set {
            // Check if the SQL statement is empty
            if (strlen($value) > 0) {
                $this->sql = $value;
                // Check if the SQL statement is a valid SQL statement
                if (!preg_match('/^(SELECT|INSERT|UPDATE|DELETE|CREATE|TRUNCATE)\s/i', trim($this->sql))) {
                    throw new Exception("Invalid SQL statement.");
                }

                // Create a new PDO instance if the SQL statement is not empty
                $this->createInstance();
            } else {
                $this->sql = '';
            }
        }
    }

    private ?array $params = [] {
        set {
            if (!empty($value)) {
                $this->params = $value;
                // Check if the number of parameters matches the number of placeholders in the SQL statement
                $placeholderCount = substr_count($this->sql, '?');
                if (count($this->params) !== $placeholderCount) {
                    throw new Exception("Number of parameters does not match number of placeholders in SQL statement.");
                }

                // Check if the parameters are valid
                foreach ($this->params as $param) {
                    if (!is_scalar($param) && !is_null($param)) {
                        throw new Exception("Invalid parameter type.");
                    }
                }
            } else {
                $this->params = [];
            }
        }
    }


    /**
     * Retrieves the read-only database connection.
     *
     * @return PDO|null The read-only PDO connection or null if not available.
     */
    public final function getReadConnection(): ?PDO
    {
        return $this->pdoRO ?: $this->createInstance(true);
    }

    /**
     * Retrieves the read-write database connection.
     *
     * @return PDO|null The read-write PDO connection or null if not available.
     */
    public final function getWriteConnection(): ?PDO
    {
        return $this->pdoRW ?: $this->createInstance();
    }

    /**
     * Enables debug mode for check current SQL query.
     *
     * @return $this
     */
    public final function debug(): Database
    {
        $this->debug = true;

        return $this;
    }

    /**
     * Forces the use of the read-write database connection.
     *
     * @return Interfaces\Database The current database instance.
     */
    protected final function forceRW(): Interfaces\Database
    {
        $this->forceReadWrite = true;
        return $this;
    }

    /**
     * Retrieves the table name associated with the current class.
     *
     * @return string The table name in snake_case format.
     */
    protected final function getTable(): string
    {
        if (method_exists($this, 'getTableName')) {
            return $this->getTableName();
        }

        $className = new ReflectionClass($this)->getShortName();
        return Helper::toSnakeCase(strtolower(str_replace('Repository', '', $className)));
    }


    /**
     * Fetches a single record from the database.
     *
     * @param string $sql The SQL query to execute.
     * @param array|null $params The parameters to bind to the query.
     * @return object|null The fetched record as an object, or null if no record is found.
     * @throws Exception If the SQL statement preparation or execution fails.
     */
    public final function fetchOne(string $sql, ?array $params = null): ?object
    {
        return $this->fetchResult($this->preparePDOStatement($sql, $params)->fetch(), 'fetchOne');
    }

    /**
     * Fetches all records from the database.
     *
     * @param string $sql The SQL query to execute.
     * @param array|null $params The parameters to bind to the query.
     * @return array The fetched records as an array of objects.
     * @throws Exception If the SQL statement preparation or execution fails.
     */
    public final function fetchAll(string $sql, ?array $params = null, ?int $fetchMode = PDO::FETCH_ASSOC): array
    {
        return $this->fetchResult($this->preparePDOStatement($sql, $params)->fetchAll(), 'fetchAll', $fetchMode) ?: [];
    }

    /**
     * Executes a SQL statement and returns the result.
     *
     * @param string $sql The SQL query to execute.
     * @param array|null $params The parameters to bind to the query.
     * @return mixed The result of the executed query.
     * @throws Exception
     */
    public final function execute (string $sql, ?array $params = null): bool
    {
        return $this->preparePDOStatement($sql, $params)->execute();
    }

    /**
     * Inserts a new record into the database.
     *
     * @param string $table The name of the table to insert into.
     * @param array $properties The properties of the record to insert.
     * @return int The ID of the newly inserted record.
     * @throws Exception If the SQL statement preparation or execution fails.
     */
    protected final function create(string $table, array $properties): int
    {
        unset($properties['forceReadWrite'], $properties['sql'], $properties['params'], $properties['table']);

        $sql = "INSERT INTO $table (" . Helper::formatArrayKeysWithBackticks($properties) . ")";
        $sql .= ' VALUES (' . Helper::arrayToPlaceholders($properties) . ')';
        $this->preparePDOStatement($sql, array_values($properties));

        return (int)$this->getWriteConnection()->lastInsertId();
    }

    /**
     * Updates an existing record in the database.
     *
     * @param string $table The name of the table to update.
     * @param array $params The properties of the record to update.
     * @param string $primaryKey The name of the primary key column.
     * @param int $id The ID of the record to update.
     * @throws Exception If the SQL statement preparation or execution fails.
     */
    protected final function updateEntity(string $table, array $params, string $primaryKey, int $id): void
    {
        unset($params['forceReadWrite'], $params['sql'], $params['params'], $params['table']);

        $sql = "UPDATE $table SET ";

        foreach ($params as $key => $value) {
            $sql .= Helper::addBackticks($key) . ' = ?, ';
        }

        $sql = rtrim($sql, ', ');

        $sql .= " WHERE " . Helper::toSnakeCase($primaryKey) . " = ?";

        $this->preparePDOStatement($sql, [$id]);
    }

    /**
     * Deletes a record from the database.
     *
     * @param string $table The name of the table to delete from.
     * @param string $primaryKey The name of the primary key column.
     * @param int $id The ID of the record to delete.
     * @throws Exception If the SQL statement preparation or execution fails.
     */
    protected final function deleteEntity(string $table, string $primaryKey, int $id): void
    {
        $sql = "DELETE FROM $table WHERE " . Helper::toSnakeCase($primaryKey) . " = ?";
        $this->preparePDOStatement($sql, [$id]);
    }

    /**
     * Sets the fetch mode for the database connection.
     *
     * @param string $sql
     * @param array|null $params
     * @return PDOStatement The current database instance.
     * @throws Exception
     */
    private function preparePDOStatement(string $sql, ?array $params = []): PDOStatement
    {
        $this->sql = $sql;
        $this->params = $params;

        if ($this->debug) {
            echo Helper::debugQuery($this->sql, $this->params);
            exit;
        }

        $statement = $this->currentInstance->prepare($this->sql);

        if ($statement === false) {
            throw new Exception("Failed to prepare SQL statement.");
        }

        $statement->execute($this->params);

        return $statement;
    }

    /**
     * Sets the fetch mode for the database connection.
     *
     * @param mixed $result
     * @param string $method
     * @param int|null $fetchMode
     * @return Interfaces\Database The current database instance.
     * @throws Exception
     */
    private function fetchResult(mixed $result, string $method, ?int $fetchMode = PDO::FETCH_ASSOC): mixed
    {
        if ($result === false) {
            return null;
        }

        if ($fetchMode !== PDO::FETCH_ASSOC) {
            return $result;
        }


        $results = [];

        if (is_array($result)) {
            if ($method === 'fetchAll') { // Array of arrays
                foreach ($result as $item) {
                    $results[] = new Helper()->convertToClassObject($item, $this->getTables());
                }
            } else {
                if ($method === 'fetchOne') {  // Single Array
                    $results = new Helper()->convertToClassObject($result, $this->getTables());
                }
            }
        }

        return $results;
    }

    /**
     * Converts the keys of an associative array to PascalCase.
     *
     * @return array The converted array with keys in PascalCase.
     */
    private function getTables(): array
    {
        $sql = "EXPLAIN " . $this->sql;

        $stmt = $this->getWriteConnection()->prepare($sql);
        $stmt->execute($this->params);
        $tables = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tables[] = Helper::toPascalCase($row['table']);
        }

        return array_flip($tables);
    }

    /**
     * Creates a new PDO instance for the database connection.
     *
     * @param bool $isRO Whether to create a read-only connection.
     * @return PDO The created PDO instance.
     */
    private function createInstance(bool $isRO = false): PDO
    {
        $user = getenv("DB_USER");
        $pass = getenv("DB_PASS");
        $host = getenv("DB_HOST");
        $db = getenv("DB_NAME");
        $port = getenv("DB_PORT_RW");

        $charset = 'utf8mb4';
        $options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset;";

        if ($isRO || str_starts_with($this->sql, "SELECT") && !$this->forceReadWrite) {
            if ($this->pdoRO === null) {
                $dsn .= "port=" . getenv("DB_PORT_RO");
                $this->pdoRO = new PDO($dsn, $user, $pass, $options);
            }

            $this->currentInstance = $this->pdoRO;

            return $this->pdoRO;
        } else {
            if ($this->pdoRW === null) {
                $dsn .= "port=" . $port;

                $this->pdoRW = new PDO($dsn, $user, $pass, $options);
            }
            $this->currentInstance = $this->pdoRW;

            return $this->pdoRW;
        }
    }


}