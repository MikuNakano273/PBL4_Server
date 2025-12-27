<?php
require_once __DIR__ . "/../Model/HashModel.php";
require_once __DIR__ . "/../Service/Github_Services.php";
$config = require_once __DIR__ . "/../config.php";
class DBVersionController
{
    public function index()
    {
        $error = null;
        $success = null;

        if (
            $_SERVER["REQUEST_METHOD"] === "POST" &&
            isset($_POST["submit_github"])
        ) {
            try {
                $model = new HashModel();
                $github = new GithubService();

                // Tạo phiên bản mới
                $newVersion = $model->createNewVersion();

                // Cập nhật các hash đang ở version 0 sang version mới
                $updatedCount = $model->finalizeHashesToVersion($newVersion);

                if ($updatedCount > 0) {
                    // Lấy dữ liệu để build nội dung file SQL
                    $hashes = $model->getHashesByVersion($newVersion);
                    $sqlContent = $this->generateSqlContent(
                        $newVersion,
                        $hashes,
                    );

                    // Lấy version mới nhất từ DB
                    $latestVersion = $model->getLatestVersion();

                    // Tạo database_version.txt
                    $versionContent = (string) $latestVersion;
                    $dataRoot = __DIR__ . "/../../PBL4_Data"; // PBL4_Data

                    $versionDir = $dataRoot . "/database";
                    if (!is_dir($versionDir)) {
                        mkdir($versionDir, 0777, true);
                    }

                    $versionFullPath = $versionDir . "/database_version.txt";
                    file_put_contents($versionFullPath, $versionContent);

                    // Push lên GitHub
                    $github->pushFile(
                        "database/database_version.txt",
                        $versionContent,
                    );

                    // Lưu file local theo cấu trúc PBL4_Data/database/vX/vX.sql
                    $config = require __DIR__ . "/../config.php";
                    $relativePath = "database/v{$newVersion}/v{$newVersion}.sql";
                    $fullPath = $config["data_root"] . "/" . $relativePath;

                    if (!file_exists(dirname($fullPath))) {
                        mkdir(dirname($fullPath), 0777, true);
                    }
                    file_put_contents($fullPath, $sqlContent);

                    // Push lên GitHub sử dụng GithubService
                    $github->pushFile($relativePath, $sqlContent);

                    // Cập nhật trạng thái trong DB
                    $model->updatePushStatus($newVersion);

                    $success = "Đã chốt Version $newVersion ($updatedCount hashes) và gửi lên GitHub thành công!";
                } else {
                    $error =
                        "Không tìm thấy mã hash mới (version 0) để cập nhật.";
                }
            } catch (Exception $e) {
                $error = "Lỗi xử lý: " . $e->getMessage();
            }
        }

        require __DIR__ . "/../View/db_version_manage.php";
    }

    // Hàm để tạo nội dung SQL cho phiên bản mới
    private function generateSqlContent($version, $hashes)
    {
        $sql = "-- PBL4 Update: Version $version\n";
        $sql .= "BEGIN TRANSACTION;\n";

        foreach ($hashes as $h) {
            $hashValue = SQLite3::escapeString($h["hash_value"]);
            $malwareName = SQLite3::escapeString($h["malware_name"]);
            $hashType = strtolower(trim($h["hash_type"])); // md5, sha1, hoặc sha256

            // Xác định đúng bảng dựa trên loại hash
            $tableName = match ($hashType) {
                "md5" => "sig_md5",
                "sha1" => "sig_sha1",
                "sha256" => "sig_sha256",
                default => null,
            };

            if ($tableName) {
                // Cấu trúc bảng mới: (hash, malware_name)
                $sql .= "INSERT OR IGNORE INTO $tableName (hash, malware_name) VALUES ('$hashValue', '$malwareName');\n";
            }
        }

        $sql .= "COMMIT;\n";
        return $sql;
    }
}
