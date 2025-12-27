<?php

class PendingReportModel
{
    private SQLite3 $db;

    public function __construct()
    {
        $this->db = new SQLite3(__DIR__ . "/../server_master.db");
        $this->db->exec("PRAGMA journal_mode = WAL;");
    }

    // Thêm báo cáo mới từ JSON
    public function insert(
        string $hash,
        string $type,
        ?string $fileName = null,
        ?string $malwareName = null,
        ?string $ruleMatch = null,
        ?string $vt_score = null,
    ): int {
        // Kiểm tra type hợp lệ
        $allowedTypes = ["md5", "sha1", "sha256"];
        if (!in_array(strtolower($type), $allowedTypes)) {
            throw new InvalidArgumentException("Invalid hash type: $type");
        }

        // Nếu không có file_name thì đặt mặc định
        if (!$fileName) {
            $fileName = "unknown_file";
        }

        $stmt = $this->db->prepare("
            INSERT INTO pending_reports
            (hash_value, type, file_name, malware_file, detected_by_rule, vt_score, created_at)
            VALUES (:hash, :type, :file_name, :malware_file, :rule, :score, :created_at)
        ");

        $stmt->bindValue(":hash", $hash, SQLITE3_TEXT);
        $stmt->bindValue(":type", strtolower($type), SQLITE3_TEXT);
        $stmt->bindValue(":file_name", $fileName, SQLITE3_TEXT);
        $stmt->bindValue(":malware_file", $malwareName ?? "", SQLITE3_TEXT);
        $stmt->bindValue(":rule", $ruleMatch ?? "", SQLITE3_TEXT);
        $stmt->bindValue(":vt_score", $vt_score ?? "", SQLITE3_TEXT);
        $stmt->bindValue(":created_at", date("Y-m-d H:i:s"), SQLITE3_TEXT);

        $stmt->execute();

        return $this->db->lastInsertRowID();
    }

    public function countRecentReports($seconds)
    {
        // SQLite sử dụng datetime('now', '-X seconds')
        $sql = "SELECT COUNT(*) as count FROM pending_reports
                WHERE created_at >= datetime('now', '-$seconds seconds')";

        $result = $this->db->querySingle($sql);
        return $result ? (int) $result : 0;
    }
}
