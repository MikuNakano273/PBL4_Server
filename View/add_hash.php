<style>
:root {
    --primary: #4f46e5;
    --primary-hover: #4338ca;
    --bg: #f4f6f9;
    --card: #ffffff;
    --border: #e5e7eb;
    --text: #111827;
    --muted: #6b7280;
    --radius: 10px;
}

body {
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background: var(--bg);
    color: var(--text);
}

.admin-card {
    max-width: 520px;
    margin: 60px auto;
    background: var(--card);
    padding: 30px;
    border-radius: var(--radius);
    box-shadow: 0 10px 25px rgba(0,0,0,.08);
}

.admin-card h1 {
    margin: 0 0 25px;
    font-size: 22px;
    font-weight: 600;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 6px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid var(--border);
    font-size: 14px;
    transition: border .2s, box-shadow .2s;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(79,70,229,.15);
}

.actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 25px;
}

button {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
}

button:hover {
    background: var(--primary-hover);
}

.back-link {
    font-size: 14px;
    color: var(--muted);
    text-decoration: none;
}

.back-link:hover {
    color: var(--primary);
    text-decoration: underline;
}
</style>

<div class="admin-card">
    <h1>Add Hash (Admin)</h1>

    <form method="post" action="?page=add_hash">
        <div class="form-group">
            <label>Hash value</label>
            <input type="text" name="hash" placeholder="Enter hash value" required>
        </div>

        <div class="form-group">
            <label>Hash type</label>
            <select name="hash_type">
                <option value="md5">MD5</option>
                <option value="sha1">SHA1</option>
                <option value="sha256">SHA256</option>
            </select>
        </div>

        <div class="form-group">
            <label>Malware name <span style="color:#9ca3af">(optional)</span></label>
            <input type="text" name="malware_name" placeholder="Example: Trojan.Generic">
        </div>

        <div class="actions">
            <button type="submit">Add hash</button>
        </div>
    </form>
</div>
