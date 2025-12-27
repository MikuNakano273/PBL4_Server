<?php
require_once __DIR__ . "/../Composer/vendor/autoload.php"; // composer autoload

use Dotenv\Dotenv;

class GithubService
{
    private string $token;
    private string $owner;
    private string $repo;
    private string $branch;

    public function __construct()
    {
        // Load .env
        $dotenv = Dotenv::createImmutable(__DIR__ . "/../Core");
        $dotenv->safeLoad(); // safeLoad không báo lỗi nếu .env không tồn tại

        $this->token = $_ENV["GITHUB_TOKEN"] ?? "";
        $this->owner = $_ENV["GITHUB_OWNER"] ?? "";
        $this->repo = $_ENV["GITHUB_REPO"] ?? "";
        $this->branch = $_ENV["GITHUB_BRANCH"] ?? "main";

        if (empty($this->token)) {
            throw new RuntimeException(
                "GITHUB_TOKEN chưa được thiết lập trong .env",
            );
        }
    }

    // Push file lên Github
    public function pushFile(string $filename, string $content)
    {
        $parts = explode("/", $filename);
        $encodedParts = array_map("rawurlencode", $parts);
        $encodedPath = implode("/", $encodedParts);
        $apiUrl = "https://api.github.com/repos/{$this->owner}/{$this->repo}/contents/{$encodedPath}";

        // GET file để lấy sha nếu tồn tại
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: token {$this->token}",
                "User-Agent: PBL4-Server/1.0 (PHP)",
                "Accept: application/vnd.github+json",
                "X-GitHub-Api-Version: 2022-11-28",
            ],
        ]);
        $responseGet = curl_exec($ch);
        $httpCodeGet = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($responseGet === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("cURL GET failed: $err");
        }
        curl_close($ch);

        $sha = null;
        if ($httpCodeGet === 200) {
            $jsonGet = json_decode($responseGet, true);
            $sha = $jsonGet["sha"] ?? null;
        } elseif ($httpCodeGet !== 404) {
            $decoded = json_decode($responseGet, true);
            $msg = $decoded["message"] ?? $responseGet;
            $doc = $decoded["documentation_url"] ?? null;
            $hint =
                "Check that owner/repo/branch are correct and token has appropriate repo permissions.";
            $extra = $doc ? " See: $doc" : "";
            throw new RuntimeException(
                "GitHub GET returned HTTP $httpCodeGet: $msg$extra - $hint",
            );
        }

        // PUT để tạo / cập nhật file
        $data = [
            "message" => "Update {$filename}",
            "content" => base64_encode($content),
            "branch" => $this->branch,
        ];
        if ($sha) {
            $data["sha"] = $sha;
        }

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Authorization: token {$this->token}",
                "User-Agent: PBL4-Server/1.0 (PHP)",
                "Accept: application/vnd.github+json",
                "X-GitHub-Api-Version: 2022-11-28",
                "Content-Type: application/json",
            ],
        ]);
        $responsePut = curl_exec($ch);
        $httpCodePut = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($responsePut === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("cURL PUT failed: $err");
        }
        curl_close($ch);

        $decodedPut = json_decode($responsePut, true);
        if ($httpCodePut === 201 || $httpCodePut === 200) {
            return $decodedPut;
        }

        $msg = $decodedPut["message"] ?? $responsePut;
        $errors = isset($decodedPut["errors"])
            ? json_encode($decodedPut["errors"])
            : null;
        $doc = $decodedPut["documentation_url"] ?? null;
        $extra = $doc ? " See: $doc" : "";
        $errDetails = $errors ? " Errors: $errors" : "";
        throw new RuntimeException(
            "GitHub PUT returned HTTP $httpCodePut: $msg$errDetails$extra. Check owner/repo/branch and token permission.",
        );
    }
}
