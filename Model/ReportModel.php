<?php

class ReportModel
{
    protected $db;

    public function __construct()
    {
        $this->db = new SQLite3(__DIR__ . "/../server_master.db");
        $this->db->exec("PRAGMA journal_mode = WAL;");
    }

    // Lấy toàn bộ pending list
    public function getAll()
    {
        $result = $this->db->query(
            "SELECT * FROM pending_reports ORDER BY id ASC",
        );
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /*==============================
     * Lấy 1 record theo id
     *==============================*/
    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM pending_reports WHERE id = :id LIMIT 1",
        );
        $stmt->bindValue(":id", $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC) ?: null;
    }

    /*==============================
     * Lấy 1 record đầu tiên (worker)
     *==============================*/
    public function getFirstPending()
    {
        $result = $this->db->query(
            "SELECT * FROM pending_reports ORDER BY id ASC LIMIT 1",
        );
        return $result->fetchArray(SQLITE3_ASSOC) ?: null;
    }

    /*==============================
     * Xoá pending record (worker/approve)
     *==============================*/
    public function delete($id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM pending_reports WHERE id = :id",
        );
        $stmt->bindValue(":id", $id, SQLITE3_INTEGER);
        return $stmt->execute();
    }

    /*==============================
     * Thêm pending report (client gửi lên)
     *==============================*/
    public function insert($hash, $filename, $rule, $ip)
    {
        $stmt = $this->db->prepare("
            INSERT INTO pending_reports(hash_value, file_name, detected_by_rule, vt_score)
            VALUES (:hash, :fname, :rule, :score)
        ");
        $stmt->bindValue(":hash", $hash, SQLITE3_TEXT);
        $stmt->bindValue(":fname", $filename, SQLITE3_TEXT);
        $stmt->bindValue(":rule", $rule, SQLITE3_TEXT);
        $stmt->bindValue(":ip", $ip, SQLITE3_TEXT);

        return $stmt->execute();
    }

    public function getPendingAll()
    {
        $sql = "SELECT * FROM pending_reports ORDER BY id ASC";
        $result = $this->db->query($sql);

        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }
    public function addPending(
        $hash,
        $fileName = "manual",
        $detectedBy = "admin",
        $malwareName = "Unknown",
    ) {
        $stmt = $this->db->prepare("
            INSERT INTO pending_reports (hash_value, file_name, detected_by_rule, malware_name, status)
            VALUES (:hash, :file, :detected, :malware, 'pending')
        ");
        $stmt->bindValue(":hash", $hash);
        $stmt->bindValue(":file", $fileName);
        $stmt->bindValue(":detected", $detectedBy);
        $stmt->bindValue(":malware", $malwareName);
        return $stmt->execute();
    }

    public function getPendingWithoutVT()
    {
        // Chỉ lấy những bản ghi có vt_score là NULL hoặc rỗng
        $sql =
            "SELECT * FROM pending_reports WHERE vt_score IS NULL OR vt_score = '' ORDER BY id ASC";
        $result = $this->db->query($sql);
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    // Cập nhật điểm VT của bản ghi (worker)
    public function updateVtscore($id, $score)
    {
        $sql = "UPDATE pending_reports SET vt_score = :score WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(":score", $score);
        $stmt->bindValue(":id", $id);
        return $stmt->execute();
    }

    // Cập nhật trạng thái của bản ghi (worker)
    public function updateStatus($id, $status)
    {
        $sql = "UPDATE pending_reports SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(":status", $status);
        $stmt->bindValue(":id", $id);
        return $stmt->execute();
    }
}
