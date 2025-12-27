<?php
require_once __DIR__ . "/../Model/ReportModel.php";
require_once __DIR__ . "/../Model/OfficialHashModel.php";
require_once __DIR__ . "/../Model/notify.php";

class AdminController
{
    protected $reportModel;
    protected $officialModel;
    protected $config;

    public function __construct()
    {
        $this->reportModel = new ReportModel();
        $this->officialModel = new OfficialHashModel();
        $this->config = require __DIR__ . "/../config.php";
    }

    // Form thêm hash thủ công
    public function addHashForm()
    {
        view("add_hash", []);
    }

    // Hàm xử lí thêm hash thủ công
    public function addHashSubmit()
    {
        $hash = trim($_POST["hash"] ?? "");
        $hash_type = trim($_POST["hash_type"] ?? "");
        $malware_name = trim($_POST["malware_name"] ?? "Unknown");

        if (!$hash || !$hash_type) {
            $_SESSION["flash"] = "Hash and type are required.";
            header("Location: ?page=add_hash");
            exit();
        }

        $this->officialModel->addHash(
            $hash,
            $hash_type,
            $malware_name,
            "admin",
        );

        $_SESSION["flash"] = "Hash added to official list.";
        header("Location: ?page=add_hash");
        exit();
    }

    // Hàm xác định loại hash
    protected function detectHashType($hash)
    {
        $len = strlen($hash);
        return match ($len) {
            32 => "md5",
            40 => "sha1",
            64 => "sha256",
            default => "unknown",
        };
    }

    // Hàm xử lí phê duyệt hash
    public function approve($id)
    {
        try {
            if (ob_get_length()) {
                ob_clean();
            }
            header("Content-Type: application/json");

            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                http_response_code(405);
                echo json_encode([
                    "ok" => false,
                    "error" => "Method not allowed",
                ]);
                return;
            }

            $data = json_decode(file_get_contents("php://input"), true);
            $id = (int) ($data["id"] ?? 0);

            if ($id <= 0) {
                echo json_encode(["ok" => false, "error" => "Invalid ID"]);
                return;
            }

            $row = $this->reportModel->getById($id);
            if (!$row) {
                echo json_encode(["ok" => false, "error" => "Not found"]);
                return;
            }

            $hash = $row["hash_value"];
            $type = $this->detectHashType($hash);

            // add official
            $this->officialModel->addHash(
                $hash,
                $type,
                $row["malware_name"] ?? "Unknown",
                "admin",
            );

            // delete pending
            $this->reportModel->delete($id);

            // GỬI THÔNG BÁO REAL-TIME
            if (function_exists("notifyRealtime")) {
                notifyRealtime([
                    "type" => "pending_removed",
                    "id" => (int) $id,
                ]);
            }

            echo json_encode(["ok" => true]);
            exit();
        } catch (Exception $e) {
            error_log("Approve Error: " . $e->getMessage());
        }
    }

    // Hàm xử lí từ chối hash
    public function reject($id)
    {
        if (ob_get_length()) {
            ob_clean();
        }
        header("Content-Type: application/json");

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            echo json_encode(["ok" => false, "error" => "Method not allowed"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($data["id"] ?? 0);

        if ($id <= 0) {
            echo json_encode(["ok" => false, "error" => "Invalid ID"]);
            return;
        }

        $row = $this->reportModel->getById($id);
        if (!$row) {
            echo json_encode(["ok" => false, "error" => "Not found"]);
            return;
        }

        // Reject = chỉ xóa khỏi pending
        $this->reportModel->delete($id);

        // GỬI THÔNG BÁO REAL-TIME
        if (function_exists("notifyRealtime")) {
            notifyRealtime([
                "type" => "pending_removed",
                "id" => (int) $id,
            ]);
        }

        echo json_encode(["ok" => true]);
        exit();
    }

    // Hàm lấy tất cả pending
    public function pending()
    {
        // Lấy tất cả pending từ model
        $pending = $this->reportModel->getPendingAll();

        require __DIR__ . "/../View/pending.php";
    }

    // Hàm kiểm tra hash type hợp lệ
    protected function isHashTypeValid($hash, $type)
    {
        $len = strlen(trim($hash));

        return ($type === "md5" && $len === 32) ||
            ($type === "sha1" && $len === 40) ||
            ($type === "sha256" && $len === 64);
    }

    // Hàm thêm hash vào pending list
    public function addHashToPending()
    {
        $hash = $_POST["hash"] ?? "";
        $hash_type = $_POST["hash_type"] ?? "";
        $malware_name = $_POST["malware_name"] ?? "Unknown";

        // Validate input
        if (!$hash) {
            $_SESSION["flash"] = "Hash is required.";
            redirect("?page=add_hash");
        }

        // Kiểm tra độ dài hash khớp loại được chọn
        if (!$this->isHashTypeValid($hash, $hash_type)) {
            $_SESSION["flash"] = "Hash length does not match selected type!";
            redirect("?page=add_hash");
        }

        // Thêm vào pending list
        $this->reportModel->addPending($hash, "manual", "admin", $malware_name);

        $_SESSION["flash"] = "Hash added to pending list.";
        redirect("?page=pending");
    }
}
