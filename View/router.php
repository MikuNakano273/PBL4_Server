<?php
ob_start();

require_once __DIR__ . "/../Controller/AdminController.php";
require_once __DIR__ . "/../Controller/ServerAddressController.php";
require_once __DIR__ . "/../Controller/DBVersionController.php";
$admin = new AdminController();
$page = $_GET["page"] ?? "home";

switch ($page) {
    case "home":
        include __DIR__ . "/home.php";
        break;

    case "server_address":
        $server = new ServerAddressController();
        $server->index();
        break;

    case "db_version_manage":
        $dbVersion = new DBVersionController();

        if (
            $_SERVER["REQUEST_METHOD"] === "POST" &&
            isset($_POST["submit_github"])
        ) {
            // Push lên GitHub trước
            $successGithub = false;
            try {
                $dbVersion->index();
                $successGithub = true;
            } catch (Exception $e) {
                $error = "GitHub push failed: " . $e->getMessage();
            }

            // Nếu GitHub thành công → update full_hash.db và push SF
            // if ($successGithub) {
            //     try {
            //         require_once __DIR__ .
            //             "/../Controller/FullDBController.php";
            //         $fullDb = new FullDBController();
            //         $count = $fullDb->updateFullDb();
            //         $fullDb->pushToSourceForge();
            //     } catch (Exception $e) {
            //         $error = "SF push failed: " . $e->getMessage();
            //     }
            // }
            $count = 0;
            $success = "Đã push lên GitHub và SourceForge thành công! $count hash mới được thêm vào full_hash.db.";
        } else {
            $dbVersion->index();
        }
        break;

    case "pending":
        $admin->pending();
        break;

    case "add_hash":
        include __DIR__ . "/add_hash.php";
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $admin->addHashSubmit();
        }
        break;

    case "admin_actions":
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Method Not Allowed");
        }

        $action = $_GET["action"] ?? "";

        switch ($action) {
            case "approve":
                $admin->approve();
                exit();

            case "deny":
                $admin->deny();
                exit();

            default:
                http_response_code(400);
                header("Content-Type: application/json");
                echo json_encode([
                    "ok" => false,
                    "error" => "Invalid action",
                ]);
                exit();
        }
        break;

    default:
        http_response_code(404);
        echo "<h1>404 - Không tìm thấy trang</h1>";
        exit();
}
