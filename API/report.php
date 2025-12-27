<?php
require_once __DIR__ . "/../Controller/PendingReportController.php";
header("Content-Type: application/json");

// Nhận dữ liệu JSON từ client
$input = json_decode(file_get_contents("php://input"), true);

if (
    !$input ||
    !isset($input["encrypted_key"], $input["iv"], $input["encrypted_data"])
) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON"]);
    exit();
}

// Giải AES key bằng RSA private key
$rsa_private_key_pem = file_get_contents(__DIR__ . "/../server_private.pem");
$private_key = openssl_pkey_get_private($rsa_private_key_pem);
$encrypted_key = base64_decode($input["encrypted_key"]);

if (!openssl_private_decrypt($encrypted_key, $aes_key, $private_key)) {
    http_response_code(400);
    echo json_encode(["error" => "RSA decryption failed"]);
    exit();
}

// Giải mã dữ liệu AES-256-CBC
$iv = base64_decode($input["iv"]);
$encrypted_data = base64_decode($input["encrypted_data"]);

$decrypted = openssl_decrypt(
    $encrypted_data,
    "aes-256-cbc",
    $aes_key,
    OPENSSL_RAW_DATA,
    $iv,
);

if ($decrypted === false) {
    http_response_code(400);
    echo json_encode(["error" => "AES decryption failed"]);
    exit();
}

// Decode JSON giải mã
$data = json_decode($decrypted, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid decrypted JSON"]);
    exit();
}

// Lưu vào folder received_files
$folder = __DIR__ . "/../received_files";
if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

$reports = [];
if (isset($data["hash"], $data["type"])) {
    $reports[] = $data;
} elseif (is_array($data)) {
    $reports = $data;
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid decrypted JSON format"]);
    exit();
}

foreach ($reports as $index => $report) {
    $filename = $folder . "/report_" . uniqid() . "_{$index}.json"; // tránh trùng file
    file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));
}

// Gọi Controller xử lý DB + notify realtime
$controller = new PendingReportController();
$controller->processReceivedFiles();

// Trả về response JSON
echo json_encode(["status" => "success"]);
