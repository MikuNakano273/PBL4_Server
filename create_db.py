import sqlite3

conn = sqlite3.connect("server_master.db")
cursor = conn.cursor()

schema = """
CREATE TABLE IF NOT EXISTS pending_reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hash_value TEXT NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('md5', 'sha1', 'sha256')),
    file_name TEXT NOT NULL,
    malware_file TEXT DEFAULT '',
    detected_by_rule TEXT DEFAULT '',
    vt_score TEXT DEFAULT '',
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS official_hashes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hash_value TEXT NOT NULL UNIQUE,
    hash_type TEXT NOT NULL,
    malware_name TEXT DEFAULT 'Unknown',
    added_by TEXT DEFAULT 'community',
    is_pushed INTEGER DEFAULT 0,
    date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
    db_version INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS db_versions (
    version_id INTEGER PRIMARY KEY AUTOINCREMENT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
"""

cursor.executescript(schema)
conn.commit()
conn.close()

print("Database created!")
