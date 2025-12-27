<?php
require_once __DIR__ . "/../Model/PendingReportModel.php";
header("Content-Type: application/json; charset=utf-8");

// CẤU HÌNH NGƯỠNG (THRESHOLD)
$maxReportsPerMinute = 5000; // Giới hạn 5000 file mỗi phút
$timeWindow = 60; // Khoảng thời gian kiểm tra (giây)

$model = new PendingReportModel();

try {
    // Gọi hàm đếm số lượng report
    $currentCount = $model->countRecentReports($timeWindow);

    if ($currentCount >= $maxReportsPerMinute) {
        // Nếu vượt ngưỡng thì báo bận
        echo json_encode([
            "status" => "busy",
            "message" => "Too many reports, please wait.",
        ]);
        exit();
    }

    // Nếu vẫn dưới ngưỡng, cho phép tiếp tục
    echo json_encode([
        "status" => "ok",
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
