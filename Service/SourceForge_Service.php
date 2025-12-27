<?php
require_once __DIR__ . "/../Composer/vendor/autoload.php";

use Dotenv\Dotenv;

class SourceForgeService
{
    private string $username;
    private string $project;
    private string $host;
    private string $basePath;

    public function __construct()
    {
        // Load .env
        $dotenv = Dotenv::createImmutable(__DIR__ . "/../Core");
        $dotenv->safeLoad();

        $this->username = $_ENV["SF_USERNAME"] ?? "";
        $this->project = $_ENV["SF_PROJECT"] ?? "";
        $this->host = $_ENV["SF_HOST"] ?? "frs.sourceforge.net";
        $this->basePath = $_ENV["SF_REMOTE_BASE"] ?? "/home/frs/project";

        if (empty($this->username) || empty($this->project)) {
            throw new RuntimeException(
                "SF_USERNAME hoặc SF_PROJECT chưa được thiết lập trong .env",
            );
        }
    }

    // Upload file lên SourceForge (thay cho pushFile GitHub)
    public function pushFile(string $localFile, string $remoteName = null): bool
    {
        if (!file_exists($localFile)) {
            throw new RuntimeException("File không tồn tại: $localFile");
        }

        $remoteName ??= basename($localFile);

        $remotePath = sprintf(
            "%s@%s:%s/%s/%s",
            $this->username,
            $this->host,
            $this->basePath,
            $this->project,
            $remoteName,
        );

        $cmd = sprintf(
            "scp %s %s 2>&1",
            escapeshellarg($localFile),
            escapeshellarg($remotePath),
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                "SourceForge upload failed:\n" . implode("\n", $output),
            );
        }

        return true;
    }
}
