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
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap');

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Quicksand', sans-serif;
    }

    body {
      display: flex;
      min-height: 100vh;
      background: #000;
    }

    .msg {
      color: #fff;
    }

    .content {
      max-width: 900px;
      margin: 30px auto;
      padding: 20px;
      background: none;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    section {
      position: absolute;
      width: 100vw;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 2px;
      flex-wrap: wrap;
      overflow: hidden;
    }

    section::before {
      content: '';
      position: absolute;
      width: 100%;
      height: 100%;
      background: linear-gradient(#000, #0f0, #000);
      animation: animate 5s linear infinite;
    }

    @keyframes animate {
      0% {
        transform: translateY(-100%);
      }

      100% {
        transform: translateY(100%);
      }
    }

    section span {
      position: relative;
      display: block;
      width: calc(6.25vw - 2px);
      height: calc(6.25vw - 2px);
      background: #181818;
      z-index: 2;
      transition: 1.5s;
    }

    section span:hover {
      background: #0f0;
      transition: 0s;
    }

    section .signup {
      position: absolute;
      width: 400px;
      background: #222;
      z-index: 1000;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px;
      border-radius: 4px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 9);
    }

    section .signup .content {
      position: relative;
      width: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      gap: 10px;
    }

    section .signup .content h2 {
      font-size: 2em;
      color: #daff1f;
      text-transform: uppercase;
    }

    section .signup .content .form {
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 25px;
    }

    section .signup .content .form .inputBox {
      position: relative;
      width: 100%;
    }

    section .signup .content .form .inputBox input {
      position: relative;
      width: 100%;
      background: #333;
      border: none;
      outline: none;
      padding: 25px 10px 7.5px;
      border-radius: 4px;
      color: #daff1f;
      font-weight: 500;
      font-size: 1em;
    }

    section .signup .content .form .inputBox i {
      position: absolute;
      left: 0;
      padding: 15px 10px;
      font-style: normal;
      color: #aaa;
      transition: 0.5s;
      pointer-events: none;
    }

    .signup .content .form .inputBox input:focus~i,
    .signup .content .form .inputBox input:valid~i {
      transform: translateY(-7.5px);
      font-size: 0.8em;
      color: #fff;
    }

    .signup .content .form .links {
      position: relative;
      width: 100%;
      display: flex;
      justify-content: space-between;
    }

    .signup .content .form .links a {
      color: #daff1f;
      text-decoration: none;
    }

    .signup .content .form .links a:nth-child(2) {
      color: #0f0;
      font-weight: 600;
    }

    .signup .content .form .inputBox input[type="submit"] {
      padding: 10px;
      background: #0f0;
      color: #000;
      font-weight: 600;
      font-size: 1.35em;
      letter-spacing: 0.05em;
      cursor: pointer;
    }

    input[type="submit"]:active {
      opacity: 0.6;
    }

    @media (max-width: 900px) {
      section span {
        width: calc(10vw - 2px);
        height: calc(10vw - 2px);
      }
    }

    @media (max-width: 600px) {
      section span {
        width: calc(20vw - 2px);
        height: calc(20vw - 2px);
      }
    }

    a.back {
      color: #fff;
    }
  </style>
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

<script>
  // Check if two passwords match
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