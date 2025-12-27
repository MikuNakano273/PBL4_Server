<?php

class OfficialHashModel
{
    protected $db;

    public function __construct()
    {
        $this->db = new SQLite3(__DIR__ . "/../server_master.db");
        $this->db->exec("PRAGMA journal_mode = WAL;");
    }

    // Thêm hash mới
    public function addHash(
        $hash_value,
        $hash_type,
        $malware_name = "Unknown",
        $added_by = "community",
        $version = 0,
    ) {
        if ($this->exists($hash_value)) {
            return true;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO official_hashes (hash_value, hash_type, malware_name, added_by, db_version)
              VALUES (:hash, :hash_type, :malware_name, :added_by, :version)",
        );
        if (!$stmt) {
            throw new Exception(
                "Failed to prepare statement: " . $this->db->lastErrorMsg(),
            );
        }

        $stmt->bindValue(":hash", $hash_value, SQLITE3_TEXT);
        $stmt->bindValue(":hash_type", $hash_type, SQLITE3_TEXT);
        $stmt->bindValue(":malware_name", $malware_name, SQLITE3_TEXT);
        $stmt->bindValue(":added_by", $added_by, SQLITE3_TEXT);
        $stmt->bindValue(":version", $version, SQLITE3_INTEGER);

        return $stmt->execute() !== false;
    }

    // Kiểm tra hash đã tồn tại chưa
    public function exists($hash_value)
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM official_hashes WHERE hash_value = :hash LIMIT 1",
        );
        if (!$stmt) {
            throw new Exception(
                "Failed to prepare statement: " . $this->db->lastErrorMsg(),
            );
        }

        $stmt->bindValue(":hash", $hash_value, SQLITE3_TEXT);
        $result = $stmt->execute();
        return (bool) $result->fetchArray();
    }

    // Lấy tất cả hash
    public function all()
    {
        $result = $this->db->query(
            "SELECT * FROM official_hashes ORDER BY date_added DESC",
        );

        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }
}
