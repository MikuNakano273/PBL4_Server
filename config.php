<?php

return [
    // Đường dẫn tới repo dữ liệu (PBL4_Data)
    "data_root" =>
        getenv("DATA_REPO_PATH") ?: realpath(__DIR__ . "/../PBL4_Data"),

    // Đường dẫn tới SourceForge repo (chứa full_hash.db)
    "sourceforge_root" =>
        getenv("SF_REPO_PATH") ?: realpath(__DIR__ . "/../SourceForge"),

    // Path tới SQLite DB, nếu biến môi trường DB_PATH không có thì dùng mặc định
    "db_path" => getenv("DB_PATH") ?: __DIR__ . "/server_master.db",

    // API key VirusTotal, khuyến nghị chỉ lấy từ ENV, không hardcode
    "virustotal_api_key" => getenv("VT_API_KEY") ?: null,

    // Repo GitHub
    "github_repo" => getenv("GITHUB_REPO") ?: null,
];
