<?php
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Bỏ qua comment và dòng trống
        if ($line === "" || str_starts_with($line, "#")) {
            continue;
        }

        // Chỉ chấp nhận dòng KEY=VALUE
        if (!str_contains($line, "=")) {
            continue;
        }

        [$key, $value] = explode("=", $line, 2);
        $key = trim($key);
        $value = trim($value, "\"' "); // loại bỏ nháy và khoảng trắng

        // Nạp vào môi trường
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("$key=$value");
    }
}

// LOAD ENV
loadEnv(__DIR__ . "/Core/.env");

// CONFIG
$config = require __DIR__ . "/config.php";

// VIEW HELPER
function view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $viewFile = __DIR__ . "/View/$name.php";

    if (!file_exists($viewFile)) {
        throw new RuntimeException("View '$name' not found!");
    }

    require $viewFile;
}

function redirect(string $url): void
{
    header("Location: $url");
    exit();
}

// DATABASE (SQLite)
function get_db(): PDO
{
    global $config;
    static $db = null;

    if ($db === null) {
        $db = new PDO("sqlite:" . $config["db_path"]);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    return $db;
}
