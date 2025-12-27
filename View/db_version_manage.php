<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Database Deployment</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            padding-top: 100px;
        }
        .box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 450px;
            text-align: center;
        }
        .btn {
            background: #2ea44f;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            width: 100%;
        }
        .btn:hover {
            background: #2c974b;
        }
        .msg {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        .err {
            background: #ffeef0;
            color: #d73a49;
            border: 1px solid #f97583;
        }
        .suc {
            background: #dcffe4;
            color: #1a7f37;
            border: 1px solid #34d058;
        }
    </style>
</head>
<body>
    <div class="box">
        <h3>PBL4 Database Manager</h3>
        <p style="color: #666; font-size: 14px;">Hệ thống sẽ chốt các hash từ Version cũ sang Version mới và đẩy lên GitHub, sau đó push toàn bộ db client lên sourceforge.</p>

        <?php if ($error): ?>
            <div class="msg err">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="msg suc">OK <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <button type="submit" name="submit_github" class="btn">Submit database to github & sourceforge</button>
        </form>
    </div>
</body>
</html>
"""""""""" """"""""""
        (
            ( """"
             ,
        )
    )""""""
                    ""
                    """"""""""{
                    ""
                }""{
                    ""
                }



                ,



                ,

                    ""

                    ""

                    ""


                "",



                "",

                """"

                "",
