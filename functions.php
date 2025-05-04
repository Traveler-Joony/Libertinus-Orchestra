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

/*-------------------  커뮤니티 관련 함수  -------------------*/

// 게시판 권한 확인 (글쓰기 권한)
function can_write_post(int $board_id): bool {
  global $pdo;
  
  if (!is_logged_in()) {
    return false;
  }
  
  // 관리자는 모든 게시판에 글 작성 가능
  if (is_admin()) {
    return true;
  }
  
  // 일반 사용자는 admin_only가 아닌 게시판에만 글 작성 가능
  $stmt = $pdo->prepare("SELECT admin_only FROM community_boards WHERE id = ?");
  $stmt->execute([$board_id]);
  $board = $stmt->fetch();
  
  if (!$board) {
    return false;
  }
  
  return $board['admin_only'] == 0;
}

// 댓글 권한 확인 (모든 로그인 사용자는 댓글 작성 가능)
function can_write_comment(): bool {
  return is_logged_in();
}

// 답글 작성 가능 여부 확인 (2단계 제한)
function can_write_reply(int $comment_id): bool {
  global $pdo;
  
  if (!is_logged_in()) {
    return false;
  }
  
  // 댓글 정보 가져오기
  $stmt = $pdo->prepare("SELECT parent_id FROM community_comments WHERE id = ?");
  $stmt->execute([$comment_id]);
  $comment = $stmt->fetch();
  
  if (!$comment) {
    return false;
  }
  
  // 최상위 댓글이면 답글 작성 가능 (1단계), 답글이면 더 이상 답글 작성 불가 (최대 2단계)
  return $comment['parent_id'] === null;
}

// 알림 생성 함수
function create_notification(int $user_id, string $type, int $related_id, string $content, int $post_id, int $sender_id = null): void {
  global $pdo;
  
  // 본인이 남긴 댓글/답글에는 알림 생성하지 않음
  if ($sender_id === $user_id) {
    return;
  }
  
  try {
    $stmt = $pdo->prepare("
      INSERT INTO notifications (user_id, type, related_id, content, post_id, sender_id, created_at) 
      VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $type, $related_id, $content, $post_id, $sender_id]);
  } catch (Exception $e) {
    // 알림 생성 실패해도 진행 (중요하지 않은 기능)
    error_log("알림 생성 실패: " . $e->getMessage());
  }
}

// 시간 경과 표시 함수 (예: '방금 전', '3분 전', '2시간 전', '3일 전' 등)
function get_time_ago(string $datetime): string {
  $time = strtotime($datetime);
  $now = time();
  $diff = $now - $time;
  
  if ($diff < 60) {
    return '방금 전';
  } elseif ($diff < 3600) {
    $minutes = floor($diff / 60);
    return $minutes . '분 전';
  } elseif ($diff < 86400) {
    $hours = floor($diff / 3600);
    return $hours . '시간 전';
  } elseif ($diff < 604800) {
    $days = floor($diff / 86400);
    return $days . '일 전';
  } elseif ($diff < 2592000) {
    $weeks = floor($diff / 604800);
    return $weeks . '주 전';
  } elseif ($diff < 31536000) {
    $months = floor($diff / 2592000);
    return $months . '개월 전';
  } else {
    $years = floor($diff / 31536000);
    return $years . '년 전';
  }
}

// 파일 확장자로 아이콘 클래스 반환
function get_file_icon_class(string $extension): string {
  $extension = strtolower($extension);
  
  switch ($extension) {
    case 'pdf':
      return 'fa-file-pdf';
    case 'doc':
    case 'docx':
      return 'fa-file-word';
    case 'xls':
    case 'xlsx':
      return 'fa-file-excel';
    case 'ppt':
    case 'pptx':
      return 'fa-file-powerpoint';
    case 'zip':
    case 'rar':
    case '7z':
      return 'fa-file-archive';
    case 'jpg':
    case 'jpeg':
    case 'png':
    case 'gif':
    case 'webp':
      return 'fa-file-image';
    case 'mp3':
    case 'wav':
    case 'ogg':
      return 'fa-file-audio';
    case 'mp4':
    case 'avi':
    case 'mov':
      return 'fa-file-video';
    case 'txt':
      return 'fa-file-alt';
    default:
      return 'fa-file';
  }
}

// 파일 크기 포맷팅 함수
function format_file_size(int $size): string {
  $units = ['B', 'KB', 'MB', 'GB', 'TB'];
  $i = 0;
  
  while ($size >= 1024 && $i < count($units) - 1) {
    $size /= 1024;
    $i++;
  }
  
  return round($size, 2) . ' ' . $units[$i];
}