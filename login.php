<?php
session_start();

// 1. check if already logged-in, if yes -> go home
if (!empty($_SESSION['login'])) {
  header("Location:index.php?msg=already_logged_in");
  exit;
}

// 2. Map all possible messages from other pages
$messages = [
  'registered'   => '註冊成功，請登入。',
  'updated'      => '資料更新成功，請重新登入。',
  'deleted'      => '帳號已註銷成功。', // added for delete.php
  'logout'       => '您已安全登出。',     // added for logout.php
  'login_failed' => '登入失敗，請確認帳號密碼是否正確。',
  'not_logged_in' => '請先登入帳號。'      // added for security kick-back
];
$msg_key = $_GET['msg'] ?? '';
$display_msg = $messages[$msg_key] ?? '';
?>
<!DOCTYPE html>
<html>

<head>
  <title>Simple Login form</title>
  <!-- load fonts and icons -->
  <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700" rel="stylesheet">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css">
  <style>
    /* CSS for layout and design */
    html,
    body {
      display: flex;
      justify-content: center;
      height: 100%;
    }

    body,
    div,
    h1,
    form,
    input,
    p {
      padding: 0;
      margin: 0;
      outline: none;
      font-family: Roboto, Arial, sans-serif;
      font-size: 16px;
      color: #666;
    }

    h1 {
      padding: 10px 0;
      font-size: 32px;
      font-weight: 300;
      text-align: center;
    }

    .main-block {
      max-width: 340px;
      min-height: 460px;
      padding: 10px 0;
      margin: auto;
      border-radius: 5px;
      border: solid 1px #ccc;
      box-shadow: 1px 2px 5px rgba(0, 0, 0, .31);
      background: #ebebeb;
    }

    /* Message style */
    .msg {
      color: #d32f2f;
      background: #ffebee;
      padding: 10px;
      margin: 0 30px 15px 30px;
      border-radius: 4px;
      font-size: 13px;
      text-align: center;
      border: 1px solid #ffcdd2;
    }

    form {
      margin: 0 30px;
    }

    input[type=text],
    input[type=password] {
      width: calc(100% - 57px);
      height: 36px;
      margin: 13px 0 0 -5px;
      padding-left: 10px;
      border-radius: 0 5px 5px 0;
      border: solid 1px #cbc9c9;
      background: #fff;
    }

    #icon {
      display: inline-block;
      padding: 9.3px 15px;
      background: #1c87c9;
      color: #fff;
      text-align: center;
      border-radius: 5px 0 0 5px;
    }

    button {
      width: 100%;
      padding: 10px 0;
      margin: 10px auto;
      border-radius: 5px;
      border: none;
      background: #1c87c9;
      color: #fff;
      font-weight: 600;
      cursor: pointer;
    }

    button:hover {
      background: #26a9e0;
    }

    .back {
      text-align: center;
      text-decoration: none;
      font-size: 13px;
      color: #666;
      margin-top: 10px;
    }
  </style>
</head>

<body>
  <div class="main-block">
    <h1>Login</h1>

    <!-- 3. If has msg in URL, show it here -->
    <?php if ($display_msg): ?>
      <div class="msg"><?php echo $display_msg; ?></div>
    <?php endif; ?>

    <!-- 4. Submit to search.php (Login Process) -->
    <form action="search.php" method="post">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
      <hr>
      <label id="icon" for="name"><i class="fas fa-user"></i></label>
      <input type="text" name="uname" id="name" placeholder="Name" required />

      <label id="icon" for="pwd"><i class="fas fa-unlock-alt"></i></label>
      <input type="password" name="pwd" id="pwd" placeholder="Password" required />

      <hr>
      <!-- Link to signup -->
      <p style="text-align:center; margin: 10px 0;">
        <a href="member_reg.php" style="font-size: 13px; color: #1c87c9;">Don't have an account? Sign up</a>
      </p>

      <div class="btn-block">
        <button type="submit">Login</button>
      </div>

      <a href="index.php" class="back" style="display: block;">Cancel and Back</a>
    </form>
  </div>
</body>

</html>