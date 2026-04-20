<?php
session_start();

// 1. Check if user is logged-in (true/false)
$isLoggedIn = !empty($_SESSION['login']);
// If yes, use their name. If no, they are just a "Visitor"
$username = $isLoggedIn ? $_SESSION['login'] : "訪客";

// 2. All messages to show on top of home page
$messages = [
    'signup_already_logged_in' => '您已經登入了，無法註冊新帳號。',
    'already_logged_in' => '您已經登入了。',
    'not_logged_in' => '你嘗試以訪客身份訪問會員頁面，請先登入。',
    'logged_out' => '您已成功登出。',
    'logout_failed' => '登出失敗，請重試。',
    'deleted' => '帳號已成功刪除。'
];
$msg_key = $_GET['msg'] ?? '';
$display_msg = $messages[$msg_key] ?? '';
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>我的網站 - 主頁</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <style>
        body {
            font-family: "Microsoft JhengHei", Arial, sans-serif;
            margin: 0;
            background-color: #f8f9fa;
        }


        /* Main content area */
        .content {
            text-align: center;
        }

        /* Error or status message style */
        .msg {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .member-actions,
        .public-actions {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        a.action-card {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            width: 150px;
            text-decoration: none;
            color: #333;
            transition: 0.2s;
        }

        a.action-card:hover {
            border-color: #007BFF;
            background: #f0f7ff;
        }

        .visitor-msg {
            color: #888;
            font-style: italic;
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <!-- 1. Navbar: Show different buttons if logged-in or not -->
    <?php require_once 'nav.php'; ?>

    <div class="content">
        <!-- 2. Show status message from URL -->
        <?php if ($display_msg): ?>
            <div class="msg"><?php echo $display_msg; ?></div>
        <?php endif; ?>

        <h1>歡迎來到首頁</h1>
        <p>目前身份：<strong><?php echo htmlspecialchars($username); ?></strong></p>

        <!--
        <div class="public-actions">
        </div>
        -->
        <!-- 3. Logic: Only members can see these cards -->
        <?php if ($isLoggedIn): ?>
            <div class="member-actions">
                <a href="product_list.php" class="action-card"> <!-- View Product List -->
                    <div>📋</div>
                    <div>查看商品列表</div>
                </a>
                <a href="show_cart.php" class="action-card">
                    <div>🛒</div>
                    <div>我的購物車</div>
                </a>
                <a href="editAccount.php" class="action-card">
                    <div>📝</div>
                    <div>修改個人資料</div>
                </a>
                <a href="cancellation.php" class="action-card">
                    <div>⚠️</div>
                    <div>註銷帳號</div>
                </a>
            </div>
        <?php else: ?>
            <!-- Visitor only message -->
            <p class="visitor-msg">登入後即可解鎖更多會員專屬功能。</p>
        <?php endif; ?>
    </div>

</body>

</html>