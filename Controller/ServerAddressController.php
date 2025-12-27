<?php
require_once __DIR__ . "/../Service/Github_Services.php";
// Đây là Controller để quản lý địa chỉ server
// Vì dùng Cloudflare free, nên cần lưu địa chỉ server vào file để Client có thể truy cập
class ServerAddressController
{
    public function index()
    {
        $error = null;
        $success = null;

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $serverAddress = trim($_POST["server_address"] ?? "");

            if ($serverAddress === "") {
                $error = "Server address không được để trống";
            } else {
                $filename = "server_address.txt";

                // tạo file local
                file_put_contents($filename, $serverAddress);

                // nếu bấm nút push github
                if (isset($_POST["submit_github"])) {
                    $github = new GithubService();
                    $github->pushFile($filename, $serverAddress);
                }

                $success = "Đã cập nhật server_address.txt";
            }
        }

        // render view
        require __DIR__ . "/../View/server_address.php";
    }
}
