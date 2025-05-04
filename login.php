<?php
$page_title = '로그인';
require_once 'config.php';
require_once 'functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf_token($_POST['csrf_token'] ?? '')) $errors[] = '잘못된 접근입니다.';
  $id = sanitize($_POST['username'] ?? '');
  $pw = $_POST['password'] ?? '';

  if (!$errors) {
    $stmt = $pdo->prepare("SELECT id, username, password, is_admin FROM users WHERE username=?");
    $stmt->execute([$id]); $u = $stmt->fetch();
    if ($u && password_verify($pw, $u['password'])) {
      $_SESSION['user_id']=$u['id']; $_SESSION['username']=$u['username']; $_SESSION['is_admin']=$u['is_admin'];
      header('Location: index.php'); exit;
    }
    $errors[] = '아이디 또는 비밀번호가 올바르지 않습니다.';
  }
}
include 'header.php';
?>

<main class="login-wrap">
  <form class="login-box" method="post" autocomplete="off">
    <h2>LIBERTINUS ORCHESTRA</h2>

    <?php foreach($errors as $e): ?>
      <p class="form-error"><?= $e; ?></p>
    <?php endforeach; ?>

    <input class="form-input" name="username" placeholder="아이디" required />
    <input class="form-input" type="password" name="password" placeholder="비밀번호" required />

    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
    <button class="btn-login">로그인</button>

    <div class="login-links">
      <a href="register.php">회원가입</a>
      <span>|</span>
      <a href="#">아이디·비밀번호 찾기</a>
    </div>
  </form>
</main>

<?php include 'footer.php'; ?>
