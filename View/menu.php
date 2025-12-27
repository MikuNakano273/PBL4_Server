<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"/>
    <title>Server Manager</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f3f3;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #444;
        }

        a.menu-link {
            display: block;
            padding: 10px 15px;
            margin: 8px 0;
            background-color: #e0e0e0;
            text-decoration: none;
            color: #000;
            border: 1px solid #ccc;
            transition: all 0.2s;
        }

        a.menu-link:hover {
            background-color: #d0d0d0;
        }

        a.menu-link.active {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
    </style>

    <script>
        function setActive(link) {
            document.querySelectorAll("a.menu-link")
                .forEach(a => a.classList.remove("active"));
            link.classList.add("active");
        }

        window.onload = function() {
            // Tự động highlight đúng tab theo URL
            const params = new URLSearchParams(window.location.search);
            const page = params.get("page") || "home";

            const link = document.querySelector(`a[data-page="${page}"]`);
            if (link) link.classList.add("active");
        };
    </script>
</head>

<body>
    <h1>Chức năng</h1>
    <hr>

    <a href="main.php?page=home" target="main" class="menu-link" data-page="home" onclick="setActive(this)">Trang chủ</a>
    <a href="main.php?page=pending" target="main" class="menu-link" data-page="pending" onclick="setActive(this)">Pending list</a>
    <a href="main.php?page=add_hash" target="main" class="menu-link" data-page="add_hash" onclick="setActive(this)">Add hash</a>
    <a href="main.php?page=server_address" target="main" class="menu-link" data-page="server_address" onclick="setActive(this)">Server address push</a>
    <a href="main.php?page=db_version_manage" target="main" class="menu-link" data-page="db_version_manage" onclick="setActive(this)">Database version manage</a>

</body>
</html>
