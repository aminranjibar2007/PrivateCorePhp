<?php

namespace core;

require_once "HTTPMonster.php";

use DarkPHP\HTTPMonster;
use JetBrains\PhpStorm\NoReturn;
use PDO;
use PDOException;

/**
 * Class Core
 *
 * A foundational utility class providing centralized server configuration,
 * HTTP response management, and environment variable handling.
 *
 * @package core
 */
final class Core extends HTTPMonster
{
    /** @var string The version identifier for the current engine instance. */
    public string $version;

    /** @var string $host the database host */
    private string $host;

    /** @var string $username the database username */
    private string $username;

    /** @var string $password the database password */
    private string $password;

    /** @var string $database the database name */
    private string $database;

    /** @var string $charset the database charset */
    private string $charset;

    /** @var PDO $pdo the PDO object for database access */
    private PDO $pdo;

    /**
        * Common HTTP status codes and their default messages.
        *
        * @var array<int, string>
     */
    public array  $responseMessages = [
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        409 => 'Conflict',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    /**
     * Core constructor.
     *
     * @param string $version The version string to initialize the engine.
     */
    public function __construct(string $version = "V1.0.0")
    {
        parent::__construct();
        $this->version = $version;
    }

    public function coreFlush(): void
    {
        flush();
    }

    public function coreClose(int $code = 0): void
    {
        exit($code);
    }

    public function redirect(string $url): void
    {
        http_response_code(301);
        header("Location: $url");
    }

    /**
     * Connect to a MySQL database using PDO.
     *
     * @param string $host
     * @param string $database
     * @param string $username
     * @param string $password
     * @param string $charset
     * @return void
     * @throws PDOException
     */
    public function connectMySqlDatabase(string $host, string $database, string $username, string $password, string $charset = 'utf8'): void
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->charset = $charset;

        // Set up the PDO object with the given parameters
        $dsn = "mysql:host=$this->host;dbname=$this->database;charset=$this->charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * Inserts a new row into the specified table with the given data.
     *
     * @param string $table the name of the table
     * @param array $data an associative array of column names and values
     *
     * @return bool true if the insert was successful, false otherwise
     */
    public function insert(string $table, array $data): bool
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $values = array_values($data);
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Retrieves rows from the specified table that match the specified criteria.
     *
     * @param string $table the name of the table
     * @param string|array $columns the names of the columns to retrieve (default: *)
     * @param array $conditions an associative array of conditions for the query (default: array())
     * @param array $options an associative array of options for the query (default: array())
     *
     * @return array an array of rows that match the query criteria
     */
    public function select(string $table, string|array $columns = '*', array $conditions = [], array $options = []): array
    {
        if (is_array($columns)) {
            $columns = implode(', ', $columns);
        }
        $sql = "SELECT $columns FROM $table";
        $where = '';
        $bindings = [];
        if (!empty($conditions)) {
            $whereArray = [];
            foreach ($conditions as $column => $value) {
                if (is_array($value)) {
                    $operator = key($value);
                    if ($operator === 'LIKE') {
                        $value = "%{$value[$operator]}%";
                    }
                    $whereArray[] = "$column $operator ?";
                    $bindings[] = $value;
                } else {
                    $whereArray[] = "$column = ?";
                    $bindings[] = $value;
                }
            }
            $where = 'WHERE ' . implode(' AND ', $whereArray);
        }
        $sql .= " $where";
        if (!empty($options)) {
            foreach ($options as $option => $value) {
                $sql .= " $option $value";
            }
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    /**
     * Updates rows in the specified table that match the specified criteria with the given data.
     *
     * @param string $table the name of the table
     * @param array $data an associative array of column names and new values
     * @param string $where the WHERE clause for the query (default: '')
     * @param array $bindings an array of values to bind to the placeholders in the WHERE clause (default: array())
     *
     * @return bool true if the update was successful, false otherwise
     */
    public function update(string $table, array $data, string $where = '', array $bindings = []): bool
    {
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = "$column = ?";
        }
        $set = implode(', ', $set);
        $sql = "UPDATE $table SET $set";
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        $values = array_values($data);
        $stmt = $this->pdo->prepare($sql);
        $values = array_merge($values, $bindings);
        return $stmt->execute($values);
    }

    /**
     * Delete rows from a table.
     *
     * @param string $table    The name of the table to delete rows from.
     * @param string $where    The WHERE clause for the delete statement.
     * @param array  $bindings An array of parameter values to bind to the SQL statement.
     *
     * @return bool Whether the delete statement was successful.
     */
    public function delete(string $table, string $where = '', array $bindings = []): bool
    {
        $sql = "DELETE FROM $table";
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($bindings);
    }

    /**
     * Get the number of rows in a table.
     *
     * @param string $table    The name of the table to count rows in.
     * @param string $where    The WHERE clause for the count statement.
     * @param array  $bindings An array of parameter values to bind to the SQL statement.
     *
     * @return int The number of rows in the table.
     */
    public function count(string $table, string $where = '', array $bindings = []): int
    {
        $sql = "SELECT COUNT(*) FROM $table";
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get the sum of a column in a table.
     *
     * @param string $table    The name of the table to sum the column in.
     * @param string $column   The name of the column to sum.
     * @param string $where    The WHERE clause for the sum statement.
     * @param array  $bindings An array of parameter values to bind to the SQL statement.
     *
     * @return int The sum of the column in the table.
     */
    public function sum(string $table, string $column, string $where = '', array $bindings = []): int
    {
        $sql = "SELECT SUM($column) FROM $table";
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Calculates the average of a column in a table.
     *
     * @param string $table    The name of the table to select from.
     * @param string $column   The name of the column to calculate the average of.
     * @param string $where    Optional WHERE clause to filter results.
     * @param array  $bindings Optional parameter bindings for the WHERE clause.
     *
     * @return mixed The average of the column as a float, or FALSE on failure.
     */
    public function avg(string $table, string $column, string $where = '', array $bindings = []): mixed
    {
        $sql = "SELECT AVG($column) FROM $table";
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchColumn();
    }

    /**
     * Finds the minimum value of a column in a table.
     *
     * @param string $table    The name of the table to select from.
     * @param string $column   The name of the column to find the minimum of.
     * @param string $where    Optional WHERE clause to filter results.
     * @param array  $bindings Optional parameter bindings for the WHERE clause.
     *
     * @return mixed The minimum value of the column, or FALSE on failure.
     */
    public function min(string $table, string $column, string $where = '', array $bindings = []): mixed
    {
        $sql = "SELECT MIN($column) FROM $table";
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchColumn();
    }

    /**
     * Finds the maximum value of a column in a table.
     *
     * @param string $table    The name of the table to select from.
     * @param string $column   The name of the column to find the maximum of.
     * @param string $where    Optional WHERE clause to filter results.
     * @param array  $bindings Optional parameter bindings for the WHERE clause.
     *
     * @return mixed The maximum value of the column, or FALSE on failure.
     */
    public function max(string $table, string $column, string $where = '', array $bindings = []): mixed
    {
        $sql = "SELECT MAX($column) FROM $table";
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchColumn();
    }

    /**
     * Runs a SQL query and returns the results.
     *
     * @param string $sql      The SQL query to run.
     * @param array  $bindings Optional parameter bindings for the SQL query.
     *
     * @return array An array of results from the SQL query.
     */
    public function query(string $sql, array $bindings = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a row from a table by ID.
     *
     * @param string $table The name of the table to select from.
     * @param int    $id    The ID of the row to select.
     *
     * @return array The row from the table with the specified ID.
     */
    public function get(string $table, int $id): array
    {
        $sql = "SELECT * FROM $table WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Retrieves a row from a table by ID using a custom SQL query.
     *
     * @param string $table   The name of the table to select from.
     * @param int    $id      The ID of the row to select.
     * @param string $columns The columns to select from the table.
     *
     * @return array The row from the table with the specified ID.
     */
    public function find(string $table, int $id, string $columns = '*'): array
    {
        $sql = "SELECT $columns FROM $table WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Retrieves multiple rows from a table.
     *
     * @param string $table    The name of the table to select from.
     * @param string $columns  The columns to select from the table.
     * @param string $where    The WHERE clause of the SQL query.
     * @param array  $bindings Optional parameter bindings for the SQL query.
     *
     * @return array An array of rows from the table that match the WHERE clause.
     */
    public function findAll(string $table, string $columns = '*', string $where = '', array $bindings = []): array
    {
        $sql = "SELECT $columns FROM $table";
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    /**
     * Saves data to a table, either by updating an existing row or creating a new one.
     *
     * @param string $table The name of the table to save to.
     * @param array  $data  An associative array of column names and values to save.
     *
     * @return bool True if the save was successful, false otherwise.
     */
    public function save(string $table, array $data): bool
    {
        if (isset($data['id'])) {
            $set = [];
            foreach ($data as $column => $value) {
                if ($column !== 'id') {
                    $set[] = "$column = ?";
                }
            }
            $set = implode(', ', $set);
            $sql = "UPDATE $table SET $set WHERE id = ?";
            $values = array_values($data);
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(array_merge($values, [$data['id']]));
        } else {
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            $values = array_values($data);
            $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
        }
    }

    /**
     * Set the HTTP response status code.
     *
     * @param int $code
     * @return void
     */
    public function responseSetStatusCode(int $code = 200): void
    {
        http_response_code($code);
    }

    /**
     * Retrieves the current HTTP request method.
     *
     * @return 'GET'|'POST'|'PUT'|'DELETE'|'HEAD'|'PATCH'|null Returns the method as a string if valid, or null if method is not set/unknown.
     */
    public function getRequestMethod(): ?string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? null;

        if (in_array($method, ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'PATCH'], true)) {
            return $method;
        }

        return null;
    }

    /**
     * Retrieves raw POST request body content.
     *
     * @return string|null The request body if present and non-empty, otherwise null.
     */
    public function getBodyDataOnPost(): ?string
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        $data = (string) file_get_contents('php://input');
        return (strlen($data) !== 0) ? $data : null;
    }

    /**
     * Sets an environment variable via the Apache server API.
     *
     * @param string $variable   The name of the environment variable.
     * @param string $value      The value to assign.
     * @param bool   $walkToTop  Whether to set the variable in the parent process.
     * @return void
     */
    public function setEnv(string $variable, string $value, bool $walkToTop = false): void
    {
        apache_setenv($variable, $value, $walkToTop);
    }

    /**
     * Retrieves an environment variable via the Apache server API.
     *
     * @param string $variable The name of the environment variable.
     * @return string|false    The value of the variable, or false if not found.
     */
    public function getEnv(string $variable): mixed
    {
        return apache_getenv($variable);
    }

    /**
     * Applies global PHP configurations and security-related HTTP headers.
     *
     * @return void
     */
    public function setPhpConfigs(): void
    {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(E_ALL);
        ini_set('log_errors', 1);
        ini_set('error_log', './php_errors.log');
        ini_set('max_execution_time', 16);
        ini_set('memory_limit', '80M');
        ini_set('opcache.enable', 1);
        ini_set('opcache.memory_consumption', 80);
        ini_set('session.use_cookies', 1);
        ini_set('opcache.revalidate_freq', 0);
        ini_set('opcache.validate_freq', 1);
        ini_set('opcache.jit', 1255);

        header("X-Frame-Options: SAMEORIGIN");
        header("X-XSS-Protection: 1; mode=block");
        header("X-Content-Type-Options: nosniff");
        header_remove('X-Powered-By');

        header("Server: PrivateCore{$this->version}/" . gethostname());
    }

    /**
     * Configures connection-level settings including execution timeouts and CORS methods.
     *
     * @param bool $keep     Whether to maintain a persistent connection (Keep-Alive).
     * @param int  $timeOut  Maximum script execution time in seconds.
     * @return void
     */
    public function configureConnection(bool $keep = false, int $timeOut = 16): void
    {
        ini_set('max_execution_time', $timeOut);
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header($keep ? 'Connection: Keep-Alive' : 'Connection: Close');
    }

    /**
     * Sends a standardized JSON response and terminates script execution.
     *
     * @param array  $data    The payload to be returned in the JSON response.
     * @param int    $code    The HTTP status code.
     * @param string $message A descriptive message regarding the operation.
     * @return void
     */
    #[NoReturn]
    public function printJson(array $data = [], int $code = 200, string $message = "All is ok"): void
    {
        $response = json_encode([
            "code"    => $code,
            "message" => $message,
            "data"    => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        header('Content-Type: application/json; charset=utf-8');
        header('Content-length: ' . strlen($response));
        http_response_code($code);
        die($response);
    }

    /**
     * Sends an HTML response and terminates script execution.
     *
     * @param string $html  The HTML content to be displayed.
     * @param int    $code  The HTTP status code.
     * @return void
     */
    #[NoReturn]
    public function printHtml(string $html = "", int $code = 200): void
    {
        header('Content-Type: text/html; charset=utf-8');
        header('Content-length: ' . strlen($html));
        http_response_code($code);
        die($html);
    }

    /**
     * Sends a plain-text response and terminates script execution.
     *
     * @param string $data  The text content to be displayed.
     * @param int    $code  The HTTP status code.
     * @return void
     */
    #[NoReturn]
    public function printText(string $data = "", int $code = 200): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-length: ' . strlen($data));
        http_response_code($code);
        die($data);
    }
}
