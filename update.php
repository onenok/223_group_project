<?php
session_start();

// 1. Kick out if not POST 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: editAccount.php?msg=try_to_access_directly");
  exit;
}

require_once("connect.php");

// 2. Check login
if (empty($_SESSION['login'])) {
  header('Location: index.php?msg=not_logged_in');
  exit;
}

// 3. Get data
$oldName = $_SESSION['login'];
$oldPwd = $_POST["old_pwd"] ?? "";

// CSRF validation
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
  header('Location: editAccount.php?msg=try_to_access_directly');
  exit;
}

$newNameInput = $_POST["new_uname"] ?? "";
$confirmName = $_POST["confirm_new_uname"] ?? "";

$newPwdInput = $_POST["new_pwd"] ?? "";
$confirmPwd = $_POST["confirm_new_pwd"] ?? "";

// --- [STEP 1: AUTH CHECK] ---
// make sure old password is provided
if (empty($oldPwd)) {
  header('Location: editAccount.php?msg=old_pwd_empty');
  exit;
}

// fetch user row from member table
$sql_auth = "SELECT * FROM member WHERE loginname = ?";
$authRes = safeQuery($sql_auth, "s", [$oldName]);
if (!$authRes->success || $authRes->result->num_rows == 0) {
    header('Location: editAccount.php?msg=invalid_user_or_password');
    exit;
}
$row = $authRes->result->fetch_assoc();
// verify password hash
if (!password_verify($oldPwd, $row['pwd'])) {
    header('Location: editAccount.php?msg=invalid_user_or_password');
    exit;
}

// --- [NEW STEP: CONFIRM CHECK] ---
// check if name matches confirm
if ($newNameInput !== $confirmName) {
    header('Location: editAccount.php?msg=name_mismatch');
    exit;
}
// check if pwd matches confirm
if ($newPwdInput !== $confirmPwd) {
    header('Location: editAccount.php?msg=pwd_mismatch');
    exit;
}

// --- [STEP 2: LOGIC CHECK] Handle d/s/n table ---
if (empty($newNameInput) && empty($newPwdInput)) {
  header('Location: editAccount.php?msg=empty_fields');
  exit;
}

$isNameSame = (empty($newNameInput) || $newNameInput === $oldName);
// compare new password against existing hash
$isPwdSame = (empty($newPwdInput) || password_verify($newPwdInput, $row['pwd']));

if ($isNameSame && $isPwdSame) {
  header('Location: editAccount.php?msg=no_changes');
  exit;
}

// --- [STEP 3: CONFLICT CHECK] ---
if (!empty($newNameInput) && $newNameInput !== $oldName) {
  $sql_checkName = "SELECT * FROM member WHERE loginname = ?";
  $nameRes = safeQuery($sql_checkName, "s", [$newNameInput]);
  if ($nameRes->result && $nameRes->result->num_rows > 0) {
    header('Location: editAccount.php?msg=username_already_used');
    exit;
  }
}

// --- [STEP 4: FINAL ACTION] ---
$finalName = !empty($newNameInput) ? $newNameInput : $oldName;
$finalPwdHash = !empty($newPwdInput) ? password_hash($newPwdInput, PASSWORD_DEFAULT) : $row['pwd'];

$sql_update = "UPDATE `member` SET `loginname` = ?, `pwd` = ? WHERE `loginname` = ?";
$updateRes = safeQuery($sql_update, "sss", [$finalName, $finalPwdHash, $oldName]);

if ($updateRes->affected_rows > 0) {
  unset($_SESSION['login']); 
  header('Location: login.php?msg=updated');
  exit;
} else {
  header('Location: editAccount.php?msg=update_failed');
  exit;
}

$conn->close();
?>
