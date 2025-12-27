<?php
class HashModel
{
    private string $dbPath;
    private SQLite3 $db;

    public function __construct()
    {
        $config = require __DIR__ . "/../config.php";
        $this->dbPath = $config["db_path"];
        $this->db = new SQLite3($this->dbPath);
        $this->db->exec("PRAGMA journal_mode = WAL;");
    }

    // Trả về đường dẫn DB
    public function getDbPath(): string
    {
        return $this->dbPath;
    }

    // Trả về connection SQLite3
    public function getConnection(): SQLite3
    {
        return $this->db;
    }

    // Tạo bản ghi version mới
    public function createNewVersion(): int
    {
        $this->db->exec(
            "INSERT INTO db_versions (created_at) VALUES (CURRENT_TIMESTAMP)",
        );
        return (int) $this->db->lastInsertRowID();
    }

    // Chốt hash version 0 sang version mới
    public function finalizeHashesToVersion(int $newVersionId): int
    {
        $stmt = $this->db->prepare(
            "UPDATE official_hashes SET db_version = ? WHERE db_version = 0",
        );
        $stmt->bindValue(1, $newVersionId, SQLITE3_INTEGER);
        $stmt->execute();
        return $this->db->changes();
    }

    // Lấy hash theo version
    public function getHashesByVersion(int $versionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM official_hashes WHERE db_version = ?",
        );
        $stmt->bindValue(1, $versionId, SQLITE3_INTEGER);
        $res = $stmt->execute();

        $rows = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    // Cập nhật trạng thái push
    public function updatePushStatus(int $versionId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE official_hashes SET is_pushed = 1 WHERE db_version = ?",
        );
        $stmt->bindValue(1, $versionId, SQLITE3_INTEGER);
        $stmt->execute();
    }

    // Lấy version mới nhất
    public function getLatestVersion(): int
    {
        $v = $this->db->querySingle(
            "SELECT MAX(version_id) AS v FROM db_versions",
        );
        return (int) ($v ?? 0);
    }
}
