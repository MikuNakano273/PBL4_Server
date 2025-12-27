<?php
require_once __DIR__ . "/../Controller/AdminController.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["ok" => false, "error" => "Method not allowed"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$id = (int) ($data["id"] ?? 0);

if ($id <= 0) {
    echo json_encode(["ok" => false, "error" => "Invalid ID"]);
    exit();
}

$admin = new AdminController();
$admin->reject($id);
?>
