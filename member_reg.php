<?php session_start();
// 1. check if already logged-in, if yes -> go home
if (!empty($_SESSION["login"])) {
  header("Location:index.php?msg=signup_already_logged_in");
  exit; // safety stop
}

// 2. Map signup error messages
$messages = [
  'name_exists' => '此帳號名稱已被使用。',
  'try_to_access_directly' => '請勿直接存取，請在此填表。',
  'empty_fields' => '請填寫所有欄位。',
  'failed' => '註冊失敗，請稍後再試。',
  'passwords_dont_match' => '密碼不相符。',
];
$msg_key = $_GET['msg'] ?? '';
$display_msg = $messages[$msg_key] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Signup Form</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>

<body>
  <!-- Submit to insert.php (Signup Process) -->
  <form action="./insert.php" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <section>
      <div class="signup">
        <div class="content">
          <h2>Sign Up</h2> <!-- changed to Sign Up for clarity -->

          <!-- Show error if exists -->
          <?php if ($display_msg): ?>
            <div class="msg" style="color: red;"><?php echo $display_msg; ?></div>
          <?php endif; ?>

          <div class="form">
            <div class="inputBox">
              <input type="text" name="uname" required> <i>*Username</i>
            </div>
            <div class="inputBox">
              <input type="text" name="display_name" required> <i>*Display Name</i>
            </div>

            <div class="inputBox">
              <input type="tel" name="tel"> <i>Phone Number</i>
            </div>

            <div class="inputBox">
              <input type="text" name="addr"> <i>Address</i>
            </div>

            <div class="inputBox">
              <input type="password" name="pwd" required> <i>*Password</i>
            </div>

            <!-- Confirm Password field -->
            <div class="inputBox">
              <input type="password" name="confirm_pwd" required> <i>*Confirm Password</i>
            </div>

            <div class="inputBox">
              <input type="submit" value="Signup">
            </div>
          </div>

          <a href="index.php" class="back">Cancel and Back</a>
        </div>
      </div>
    </section>
  </form>
</body>

<script type="text/javascript">
  // Lazy JS: Check if two passwords match
  const password = document.getElementsByName("pwd")[0];
  const confirm_password = document.getElementsByName("confirm_pwd")[0];

  function validatePassword() {
    if (password.value != confirm_password.value) {
      // If not same, show browser alert bubble
      if (confirm_password.value.length > 0) {
        confirm_password.setCustomValidity("密碼不相符");
      } else {
        confirm_password.setCustomValidity("請確認密碼");
      }
    } else {
      // If same, clear the error
      confirm_password.setCustomValidity('');
    }
  }

  // trigger when typing or changing
  password.onchange = validatePassword;
  confirm_password.onkeyup = validatePassword;
</script>

</html>
