<?php
function notifyRealtime(array $data): bool
{
    // Kiểm tra xem data có rỗng không
    if (empty($data)) {
        return false;
    }

    $ch = curl_init("http://127.0.0.1:3000/notify");

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "User-Agent: PBL4-VT-Worker/1.0",
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 2, // Giảm xuống 2s để không treo worker
        CURLOPT_CONNECTTIMEOUT => 1, // Chỉ đợi kết nối trong 1s
    ]);

    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false || $code !== 200) {
        return false;
    }

    return true;
}
?>
