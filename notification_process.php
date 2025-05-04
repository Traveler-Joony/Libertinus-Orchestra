<?php
require_once 'config.php';
require_once 'functions.php';

// 로그인 확인
if (!is_logged_in()) {
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
  exit;
}

// AJAX 요청 처리
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 안 읽은 알림 개수 조회
if ($action == 'count') {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
  $stmt->execute([$_SESSION['user_id']]);
  $count = $stmt->fetchColumn();
  
  header('Content-Type: application/json');
  echo json_encode(['success' => true, 'count' => $count]);
  exit;
}

// 알림 읽음 표시
if ($action == 'mark_read' && isset($_POST['id'])) {
  $notification_id = intval($_POST['id']);
  
  // 알림 소유자 확인
  $stmt = $pdo->prepare("SELECT user_id FROM notifications WHERE id = ?");
  $stmt->execute([$notification_id]);
  $notification = $stmt->fetch();
  
  if (!$notification || $notification['user_id'] != $_SESSION['user_id']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
  }
  
  // 읽음 처리
  $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
  $stmt->execute([$notification_id]);
  
  header('Content-Type: application/json');
  echo json_encode(['success' => true]);
  exit;
}

// 모든 알림 읽음 처리
if ($action == 'mark_all_read') {
  $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  
  header('Content-Type: application/json');
  echo json_encode(['success' => true]);
  exit;
}

// 알림 삭제
if ($action == 'delete' && isset($_POST['id'])) {
  $notification_id = intval($_POST['id']);
  
  // 알림 소유자 확인
  $stmt = $pdo->prepare("SELECT user_id FROM notifications WHERE id = ?");
  $stmt->execute([$notification_id]);
  $notification = $stmt->fetch();
  
  if (!$notification || $notification['user_id'] != $_SESSION['user_id']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
  }
  
  // 삭제 처리
  $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
  $stmt->execute([$notification_id]);
  
  header('Content-Type: application/json');
  echo json_encode(['success' => true]);
  exit;
}

// 잘못된 요청
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
exit;