<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($pending) || !is_array($pending)) {
    $pending = [];
}
?>

<style>
    .hash-cell {
        display: inline-block;
        max-width: 220px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    body {
        font-family: Arial, sans-serif;
        background: #f4f6f9;
        margin: 0;
        padding: 20px;
        color: #333;
    }

    h1 {
        text-align: center;
        margin-bottom: 20px;
        color: #222;
    }

    .flash {
        background: #e5ffe5;
        border-left: 5px solid #2ecc71;
        padding: 12px;
        margin: 10px 0;
        border-radius: 4px;
        font-weight: bold;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .action-buttons {
        display: flex;
        gap: 6px;
        justify-content: center;
    }

    .action-buttons .btn {
        width: 80px;
        padding: 6px 0;
        text-align: center;
    }

    th {
        background: #2c3e50;
        color: white;
        padding: 12px 10px;
        font-size: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    td {
        padding: 10px;
        border-bottom: 1px solid #e8e8e8;
    }

    tr:hover td {
        background: #f0f7ff;
    }

    .btn-approve {
        background: #e74c3c;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 5px;
        font-size: 13px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-approve:hover {
        background: #c0392b;
    }
    .btn-reject {
        background: #e74c3c;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 5px;
        font-size: 13px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-reject:hover {
        background: #c0392b;
    }


    .text-center {
        text-align: center;
        color: #888;
    }
</style>
<script>
    function setActive(link) {
        document.querySelectorAll("a.menu-link")
            .forEach(a => a.classList.remove("active"));
        link.classList.add("active");
    }

    window.onload = function() {
        const params = new URLSearchParams(window.location.search);
        const page = params.get("page") || "home";

        const link = document.querySelector(`a[data-page="${page}"]`);
        if (link) link.classList.add("active");
    };
</script>
<?php if (!empty($_SESSION["flash"])): ?>
    <div class="flash"><?php echo htmlspecialchars($_SESSION["flash"]); ?></div>
    <?php unset($_SESSION["flash"]); ?>
<?php endif; ?>

<h1>Pending Reports</h1>

<table>
    <tr>
        <th>#</th>
        <th>Hash</th>
        <th>File</th>
        <th>Detected By</th>
        <th>VT Score</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>

    <?php if (count($pending) === 0): ?>
        <tr><td colspan="7" class="text-center">No pending reports.</td></tr>
    <?php else: ?>
        <?php foreach ($pending as $row): ?>
            <tr id="row-<?php echo htmlspecialchars($row["id"]); ?>">
                <td><?php echo htmlspecialchars($row["id"]); ?></td>
                <td>
                    <span class="hash-cell"
                          title="<?php echo htmlspecialchars(
                              $row["hash_value"],
                          ); ?>">
                        <?php echo htmlspecialchars($row["hash_value"]); ?>
                    </span>
                </td>

                <td><?php echo htmlspecialchars($row["file_name"]); ?></td>
                <td><?php echo htmlspecialchars(
                    $row["detected_by_rule"],
                ); ?></td>
                <td><?php echo htmlspecialchars(
                    $row["vt_score"] ?? "N/A",
                ); ?></td>
                <td><?php echo htmlspecialchars(
                    $row["status"] ?? "pending",
                ); ?></td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-approve"
                                onclick="approvePending(<?php echo htmlspecialchars(
                                    $row["id"],
                                ); ?>)">
                            Approve
                        </button>
                        <button class="btn btn-reject"
                                onclick="rejectPending(<?php echo htmlspecialchars(
                                    $row["id"],
                                ); ?>)">
                            Reject
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>
<script>
// Websocket real-time
const WS_URL = "ws://127.0.0.1:3000";

const ws = new WebSocket(WS_URL);

ws.onopen = () => {
    console.log("WebSocket connected");
};

ws.onclose = () => {
    console.warn("WebSocket disconnected");
};

ws.onerror = err => {
    console.error("WS error", err);
};

ws.onmessage = e => {
    const data = JSON.parse(e.data);
    console.log("Realtime event:", data);

    switch (data.type) {
        case "pending_added":
            addRow(data.row);
            break;

        case "pending_updated":
            updateRow(data.row);
            break;

        case "pending_removed":
            removeRow(data.id);
            break;
    }
};

// Hàm thêm hàng mới vào bảng
function addRow(row) {
    if (document.getElementById("row-" + row.id)) return;

    const table = document.querySelector("table");
    const tr = document.createElement("tr");
    tr.id = "row-" + row.id;
    tr.style.background = "#e8fff0";

    tr.innerHTML = `
        <td>${escapeHtml(row.id)}</td>
        <td>
            <span class="hash-cell" title="${escapeHtml(row.hash_value)}">
                ${escapeHtml(row.hash_value)}
            </span>
        </td>
        <td>${escapeHtml(row.file_name)}</td>
        <td>${escapeHtml(row.detected_by_rule ?? "-")}</td>
        <td>${escapeHtml(row.vt_score ?? "N/A")}</td>
        <td>${escapeHtml(row.status ?? "pending")}</td>
        <td>
            <div class="action-buttons">
                <button class="btn btn-approve"
                        onclick="approvePending(<?php echo htmlspecialchars(
                            $row["id"],
                        ); ?>)">
                    Approve
                </button>
                <button class="btn btn-reject"
                        onclick="rejectPending(<?php echo htmlspecialchars(
                            $row["id"],
                        ); ?>)">
                    Reject
                </button>
            </div>
        </td>

    `;
    const insertBeforeRow = table.rows.length > 1 ? table.rows[1] : null;
    if (insertBeforeRow) {
        table.insertBefore(tr, insertBeforeRow);
    } else {
        table.appendChild(tr);
    }

    setTimeout(() => tr.style.background = "", 1500);
}

// Hàm cập nhật hàng
function updateRow(row) {
    const tr = document.getElementById("row-" + row.id);
    if (!tr) return;

    tr.children[4].innerText = row.vt_score ?? tr.children[4].innerText;
    tr.children[5].innerText = row.status ?? tr.children[5].innerText;

    tr.style.background = "#fff6cc";
    setTimeout(() => tr.style.background = "", 1500);
}

// Hàm xóa hàng
function removeRow(id) {
    const tr = document.getElementById("row-" + id);
    if (!tr) return;

    tr.style.background = "#ffd6d6";
    setTimeout(() => tr.remove(), 500);
}

function escapeHtml(str) {
    return String(str ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}
</script>
<script>
function approvePending(id) {
    if (!confirm("Approve this hash?")) return;

    fetch("/api/approve.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id })
    })

    .then(res => res.json())
    .then(data => {
        if (!data.ok) {
            alert(data.error || "Approve failed: Unknown server error");
            return;
        }
        console.log("Approved:", id);
    })
    .catch(err => {
        console.error("Network or JSON parsing error:", err);
        alert("Network error: Could not reach server or invalid response format.");
    });
}

function rejectPending(id) {
    if (!confirm("Reject this hash?")) return;

    fetch("/api/reject.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id })
    })

    .then(res => res.json())
    .then(data => {
        if (!data.ok) {
            alert(data.error || "Reject failed: Unknown server error");
            return;
        }
        console.log("Rejected:", id);
    })
    .catch(err => {
        console.error("Network or JSON parsing error:", err);
        alert("Network error: Could not reach server or invalid response format.");
    });
}
</script>
