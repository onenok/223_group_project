<?php
session_start();

// 1. Check if user is logged-in, if not -> kick to index
if (empty($_SESSION['login'])) {
    header("Location: index.php?msg=not_logged_in");
    exit;
}

// 2. Translate error codes from URL to Chinese
$msgs = [
    'invalid_user' => '帳號與目前登入者不符',
    'invalid_user_or_password' => '帳號或密碼錯誤',
    'error' => '刪除失敗，請再試一次',
    'try_to_access_directly' => '請透過表單提交資料'
];
$msg_key = $_GET['msg'] ?? '';
$display_msg = $msgs[$msg_key] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Delete Account</title>
    <style>
        /* basic styling (Lazy CSS) */
        body { font-family: Arial; background: #fafafa; display: flex; justify-content: center; padding-top: 50px; }
        .box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 350px; }
        h2 { color: #d32f2f; margin-top: 0; }
        .err { color: white; background: #f44336; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        .btn-del { width: 100%; padding: 12px; background: #d32f2f; color: white; border: none; cursor: pointer; font-weight: bold; border-radius: 5px; }
        .btn-del:hover { background: #b71c1c; }
        .back { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; font-size: 13px; }
    </style>
</head>
<body>

<div class="box">
    <h2>註銷帳號</h2>
    <p style="font-size: 13px; color: #666;">Warning: This action cannot be undone.</p>

    <!-- If has error message, show it here -->
    <?php if ($display_msg): ?>
        <div class="err"><?php echo $display_msg; ?></div>
    <?php endif; ?>

    <!-- POST form to delete.php -->
    <!-- Add a pop-up alert to double check -->
    <form action="delete.php" method="POST" onsubmit="return confirm('Really want to delete?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        
        <label>Confirm Username:</label>
        <input type="text" name="uname" placeholder="Enter your username" required>

        <label>Confirm Password:</label>
        <input type="password" name="pwd" placeholder="Enter your password" required>

        <button type="submit" class="btn-del">CONFIRM DELETE</button>
    </form>

    <a href="index.php" class="back">取消並返回主頁</a>
</div>

</body>
</html>
