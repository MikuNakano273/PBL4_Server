<?php
require_once __DIR__ . "/../Model/PendingReportModel.php";
$reportModel = new PendingReportModel();
$pending = $reportModel->getPendingAll();
header("Content-Type: application/json");
echo json_encode($pending);
?>
