<?php
declare(strict_types=1);

require_once __DIR__ . "/../Model/PendingReportModel.php";
require_once __DIR__ . "/../Model/notify.php";

class PendingReportController
{
    private PendingReportModel $model;
    private string $folder;
    private string $lockFile;

    public function __construct()
    {
        $this->model = new PendingReportModel();
        $this->folder = __DIR__ . "/../received_files";
        $this->lockFile = __DIR__ . "/../process_reports.lock";

        if (!is_dir($this->folder)) {
            mkdir($this->folder, 0777, true);
        }
    }

    // Quét folder received file và xử lý các file JSON báo cáo
    public function processReceivedFiles(): void
    {
        $lockFp = fopen($this->lockFile, "w+");
        if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
            // Nếu không lấy được lock, có process khác đang chạy
            return;
        }

        try {
            $files = glob($this->folder . "/*.json");
            $files = array_filter(
                $files,
                fn($f) => !str_ends_with($f, ".processing"),
            );

            foreach ($files as $file) {
                $this->processFile($file);
                // Giải phóng bộ nhớ sau mỗi file
                gc_collect_cycles();
            }
        } finally {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
    }

    // Xử lý một file JSON đơn lẻ
    private function processFile(string $file): void
    {
        $processingFile = $file . ".processing";
        if (!rename($file, $processingFile)) {
            return;
        }

        try {
            $content = file_get_contents($processingFile);
            $report = json_decode($content, true);

            if (!$report || !isset($report["hash"], $report["type"])) {
                unlink($processingFile);
                return;
            }

            // Insert DB, nếu trùng hash => Exception
            $id = $this->model->insert(
                $report["hash"],
                $report["type"],
                $report["file_name"] ?? null,
                $report["malware_name"] ?? null,
                $report["rule_match"] ?? null,
            );

            // Notify realtime khi insert thành công
            if ($id) {
                notifyRealtime([
                    "type" => "pending_added",
                    "row" => [
                        "id" => $id,
                        "hash_value" => $report["hash"],
                        "file_name" => $report["file_name"] ?? "",
                        "detected_by_rule" => $report["rule_match"] ?? "",
                        "vt_score" => "N/A",
                        "status" => "pending",
                    ],
                ]);
            }

            unlink($processingFile);
        } catch (Exception $e) {
            echo "[" .
                date("Y-m-d H:i:s") .
                "] Error processing file $file: " .
                $e->getMessage() .
                "\n";
            if (file_exists($processingFile)) {
                unlink($processingFile);
            }
        } finally {
            unset($report, $content);
        }
    }
}
