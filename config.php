<?php
/* DB 접속 정보 ‑ 실제 값으로 교체 */
$db_host = 'localhost';
$db_name = 'shwowns65';
$db_user = 'shwowns65';
$db_pass = 'shwowns0204';

/* 운영 시 화면에 오류 노출 금지, 로그만 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors',   1);
ini_set('error_log', __DIR__ . '/error.log');

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

try {
  $pdo = new PDO(
    "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
    $db_user,
    $db_pass,
    [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ]
  );
} catch (PDOException $e) {
  error_log('DB Connection failed: ' . $e->getMessage());
  die('데이터베이스 연결 오류가 발생했습니다.');
}
?>
