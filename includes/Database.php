<?php
/**
 * Single shared PDO connection.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $c = require ROOT_PATH . '/config/database.php';
            $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset={$c['charset']}";
            try {
                self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    // keep MySQL's clock in step with PHP's timezone
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '" . date('P') . "'",
                ]);
            } catch (PDOException $ex) {
                http_response_code(500);
                exit(APP_ENV === 'development'
                    ? 'Database connection failed: ' . htmlspecialchars($ex->getMessage())
                      . '<br>Check config/database.php and make sure database/schema.sql is imported.'
                    : 'Service temporarily unavailable.');
            }
        }
        return self::$pdo;
    }
}
