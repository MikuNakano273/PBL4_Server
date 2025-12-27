<?php
declare(strict_types=1);

require_once __DIR__ . "/../Controller/PendingReportController.php";

/**
 * Worker chạy liên tục, mỗi 2 phút quét và xử lý file
 */
while (true) {
    try {
        // 1. Tạo mới controller
        $controller = new PendingReportController();

        // 2. Xử lý folder received_files
        $controller->processReceivedFiles();

        // 3. Giải phóng bộ nhớ
        unset($controller);
        gc_collect_cycles();

        // 4. Nghỉ 2 phút trước khi vòng tiếp theo
        sleep(120);
    } catch (Throwable $e) {
        // Log lỗi nhưng không dừng worker
        echo "[" . date("Y-m-d H:i:s") . "] Error: " . $e->getMessage() . "\n";
        sleep(60); // Nếu có lỗi, nghỉ 1 phút rồi tiếp tục
    }
}
