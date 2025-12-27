<?php
// Worker checkpoint WAL cho PBL4 Server
require_once __DIR__ . "/../bootstrap.php";

$dbPath = __DIR__ . "/../server_master.db";
$walPath = $dbPath . "-wal";

if (!file_exists($walPath)) {
    exit(); // WAL chưa tồn tại → không cần checkpoint
}

$walSize = filesize($walPath);

// Ngưỡng 50MB (có thể chỉnh)
$MAX_WAL_SIZE = 50 * 1024 * 1024;

if ($walSize < $MAX_WAL_SIZE) {
    exit(); // WAL còn nhỏ → bỏ qua
}

$db = new SQLite3($dbPath);
$db->exec("PRAGMA journal_mode = WAL;");

// Checkpoint an toàn, không block reader
$db->exec("PRAGMA wal_checkpoint(PASSIVE);");

// Optional log
echo date("Y-m-d H:i:s") .
    " | WAL checkpointed (" .
    round($walSize / 1024 / 1024, 2) .
    " MB)\n";
