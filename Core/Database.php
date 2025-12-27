<?php
class Database
{
    private static $instance = null;
    public static function getConnection()
    {
        if (self::$instance === null) {
            // Đường dẫn đến file database tổng
            $dbPath = __DIR__ . "/../../PBL4_Data/database/full_hash.db";

            try {
                // Verify extension available
                if (!extension_loaded("sqlite3")) {
                    throw new Exception("SQLite3 extension is not available.");
                }

                // Make SQLite3 throw exceptions on errors
                if (method_exists("SQLite3", "enableExceptions")) {
                    SQLite3::enableExceptions(true);
                }

                // Open the database (will create if not exist when flags permit)
                self::$instance = new SQLite3($dbPath);

                // Set a busy timeout (milliseconds)
                if (method_exists(self::$instance, "busyTimeout")) {
                    self::$instance->busyTimeout(5000);
                } else {
                    // Fallback to PRAGMA if method not available
                    self::$instance->exec("PRAGMA busy_timeout = 5000;");
                }

                // Recommended pragmas for better concurrency and performance
                // Use WAL journal mode for better concurrent reads/writes on many workloads
                self::$instance->exec("PRAGMA journal_mode = WAL;");
                // Balance durability vs performance
                self::$instance->exec("PRAGMA synchronous = NORMAL;");
            } catch (Exception $e) {
                // Mirror original behavior: stop execution and show message
                die("Lỗi kết nối database: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
    public static function closeConnection()
    {
        if (self::$instance !== null) {
            try {
                self::$instance->close();
            } catch (Exception $e) {
                // Ignore close errors
            } finally {
                self::$instance = null;
            }
        }
    }
}
