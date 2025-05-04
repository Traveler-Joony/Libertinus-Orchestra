<?php
/*-------------------  세션 & 공통 함수  -------------------*/
if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict',
  ]);
  session_start();
}

function generate_csrf_token(): string {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function verify_csrf_token(string $token): bool {
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitize(string $v): string {
  return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

function is_logged_in(): bool {
  return !empty($_SESSION['user_id']);
}

function is_admin(): bool {
  return is_logged_in() && !empty($_SESSION['is_admin']);
}
?>
