<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../Model/ReportModel.php";
require_once __DIR__ . "/../Model/OfficialHashModel.php";
require_once __DIR__ . "/../Model/notify.php";

$reportModel = new ReportModel();
$officialModel = new OfficialHashModel();
$config = include __DIR__ . "/../config.php";

var_dump(getenv("VT_API_KEY"));

function detectHashType($hash)
{
    return match (strlen($hash)) {
        32 => "md5",
        40 => "sha1",
        64 => "sha256",
        default => "unknown",
    };
}

function checkVT($hash, $apiKey)
{
    $url = "https://www.virustotal.com/api/v3/files/" . urlencode($hash);
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => ["x-apikey: $apiKey"],
    ]);

    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "VT HTTP: $http\n";
    if ($http === 404) {
        return ["status" => "not_found"];
    }
    if ($http !== 200) {
        return [
            "status" => "error",

            "http" => $http,
        ];
    }

    $json = json_decode($res, true);
    $stats = $json["data"]["attributes"]["last_analysis_stats"] ?? [];
    $total = array_sum($stats);
    $malicious = $stats["malicious"] ?? 0;

    $vtScore = $total > 0 ? round(($malicious / $total) * 100, 2) : 0;
    return [
        "status" => "ok",
        "malicious" => $malicious,
        "name" => $json["data"]["attributes"]["meaningful_name"] ?? "Unknown",
        "vt_score" => $total > 0 ? $malicious . "/" . $total : "0/0",
    ];
}

echo "VT worker started\n";

while (true) {
    try {
        $pending = $reportModel->getPendingAll() ?: [];

        if (!$pending) {
            echo "No pending hashes\n";
            gc_collect_cycles();
            sleep(30);
            continue;
        }

        foreach ($pending as $row) {
            $id = $row["id"];
            $hash = $row["hash_value"];
            echo "Checking: $hash\n";

            try {
                $vt = checkVT($hash, $config["virustotal_api_key"]);
            } catch (Exception $e) {
                echo "VT check failed: " . $e->getMessage() . "\n";
                sleep(30);
                continue;
            }

            $status = "";
            $vt_score = 0;

            if ($vt["status"] === "error") {
                echo "VT error, skip\n";
                sleep(30);
                continue;
            }

            if ($vt["status"] === "not_found") {
                echo "→ Not found on VT → Waiting for admin to approve\n";
                $status = "Waiting Approve";
                $vt_score = "No score";
            } elseif ($vt["malicious"] > 0) {
                echo "→ Malware detected\n";
                $status = "malware detected";
                $vt_score = $vt["malicious"];
            } else {
                echo "→ Clean\n";
                $status = "Clean";
                $vt_score = 0;
            }

            // Update DB
            $reportModel->updateStatus($id, $status);
            $reportModel->updatevtscore($id, $vt_score);

            // Notify dashboard (try/catch để không crash)
            try {
                $success = notifyRealtime([
                    "type" => "pending_updated",
                    "row" => [
                        "id" => $id,
                        "status" => $status,
                        "vt_score" => $vt_score,
                    ],
                ]);

                if (!$success) {
                    echo "Warning: Could not notify realtime server (Node.js down?)\n";
                }
            } catch (Throwable $e) {
                echo "Notify Error: " . $e->getMessage() . "\n";
            }
            unset($row, $vt, $status, $vt_score);
            gc_collect_cycles();
            sleep(15);
        }
        unset($pending);
        gc_collect_cycles();
    } catch (Exception $e) {
        echo "Worker loop exception: " . $e->getMessage() . "\n";
        gc_collect_cycles();
        sleep(30); // tránh crash loop
    }
}
