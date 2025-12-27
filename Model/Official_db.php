<?php
require_once __DIR__ . "/../server_master.db";

class OfficialModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->db->exec("PRAGMA journal_mode = WAL;");
    }

    // Lấy version ID mới nhất từ bảng db_versions
    public function createNewVersion()
    {
        $stmt = $this->db->prepare(
            "INSERT INTO db_versions (created_at) VALUES (CURRENT_TIMESTAMP)",
        );
        $stmt->execute();
        return $this->db->lastInsertId();
    }

    // Chốt các hash từ version 0 sang version mới
    public function finalizeVersion($newVersionId)
    {
        $stmt = $this->db->prepare(
            "UPDATE official_hashes SET db_version = ? WHERE db_version = 0",
        );
        $stmt->execute([$newVersionId]);
        return $stmt->rowCount();
    }

    // Lấy danh sách hash theo version để export SQL
    public function getHashesByVersion($versionId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM official_hashes WHERE db_version = ?",
        );
        $stmt->execute([$versionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Đánh dấu các hash đã được push
    public function markAsPushed($versionId)
    {
        $stmt = $this->db->prepare(
            "UPDATE official_hashes SET is_pushed = 1 WHERE db_version = ?",
        );
        return $stmt->execute([$versionId]);
    }
}
