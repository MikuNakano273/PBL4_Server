<?php
require_once __DIR__ . "/../Composer/vendor/autoload.php";
require_once __DIR__ . "/../Model/HashModel.php";
require_once __DIR__ . "/../Service/SourceForge_Service.php";
// Đây là hàm sẽ push toàn bộ cơ sở dữ liệu của client lên Sourceforge
class FullDBController
{
    private HashModel $model;
    private SourceForgeService $sf;
    private string $fullDbPath;

    public function __construct()
    {
        $this->model = new HashModel();
        $this->sf = new SourceForgeService();

        $config = require __DIR__ . "/../config.php";
        $this->fullDbPath = $config["sourceforge_root"] . "/full_hash.db";

        if (!file_exists($this->fullDbPath)) {
            // Tạo file full_hash.db nếu chưa có
            $fullDb = new SQLite3($this->fullDbPath);
            $fullDb->exec("
                CREATE TABLE IF NOT EXISTS sig_md5 (
                    hash TEXT PRIMARY KEY,
                    malware_name TEXT DEFAULT 'Unknown'
                )
            ");
            $fullDb->exec("
                CREATE TABLE IF NOT EXISTS sig_sha1 (
                    hash TEXT PRIMARY KEY,
                    malware_name TEXT DEFAULT 'Unknown'
                )
            ");
            $fullDb->exec("
                CREATE TABLE IF NOT EXISTS sig_sha256 (
                    hash TEXT PRIMARY KEY,
                    malware_name TEXT DEFAULT 'Unknown'
                )
            ");
            $fullDb->close();
        }
    }

    public function updateFullDb(): int
    {
        $serverDb = $this->model->getConnection();
        $fullDb = new SQLite3($this->fullDbPath);

        // Tối ưu DB lớn
        $fullDb->exec("PRAGMA journal_mode = WAL");
        $fullDb->exec("PRAGMA synchronous = NORMAL");
        $fullDb->exec("BEGIN");

        $latestVersion = $this->model->getLatestVersion();

        $hashes = $serverDb->query("
            SELECT hash_value, hash_type, malware_name
            FROM official_hashes
            WHERE db_version = $latestVersion
        ");

        $stmtMd5 = $fullDb->prepare(
            "INSERT OR IGNORE INTO sig_md5 (hash, malware_name) VALUES (?, ?)",
        );
        $stmtSha1 = $fullDb->prepare(
            "INSERT OR IGNORE INTO sig_sha1 (hash, malware_name) VALUES (?, ?)",
        );
        $stmtSha256 = $fullDb->prepare(
            "INSERT OR IGNORE INTO sig_sha256 (hash, malware_name) VALUES (?, ?)",
        );

        $count = 0;
        while ($row = $hashes->fetchArray(SQLITE3_ASSOC)) {
            $hash = $row["hash_value"];
            $type = $row["hash_type"];
            $name = $row["malware_name"] ?: "Unknown";

            switch ($type) {
                case "md5":
                    $stmtMd5->bindValue(1, $hash);
                    $stmtMd5->bindValue(2, $name);
                    $stmtMd5->execute();
                    break;
                case "sha1":
                    $stmtSha1->bindValue(1, $hash);
                    $stmtSha1->bindValue(2, $name);
                    $stmtSha1->execute();
                    break;
                case "sha256":
                    $stmtSha256->bindValue(1, $hash);
                    $stmtSha256->bindValue(2, $name);
                    $stmtSha256->execute();
                    break;
                default:
                    continue 2;
            }
            $count++;
        }

        // Cập nhật db_version
        $fullDb->exec("
            CREATE TABLE IF NOT EXISTS db_info (
                key TEXT PRIMARY KEY,
                value TEXT
            )
        ");
        $stmtVer = $fullDb->prepare("
            INSERT INTO db_info(key, value) VALUES ('db_version', :v)
            ON CONFLICT(key) DO UPDATE SET value = :v
        ");
        $stmtVer->bindValue(":v", (string) $latestVersion);
        $stmtVer->execute();

        $fullDb->exec("COMMIT");
        $fullDb->close();

        return $count;
    }

    // public function pushToSourceForge(): bool
    // {
    //     return $this->sf->pushFile($this->fullDbPath, "full_hash.db");
    // }
}
