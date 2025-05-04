<?php
require_once 'config.php';
require_once 'functions.php';

// 로그인 체크
if (!is_logged_in()) {
  // AJAX 요청인 경우 JSON 응답
  if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
  }
  
  // 일반 요청인 경우 리다이렉트
  echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
  exit;
}

// 액션 파라미터 확인
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if (!$action) {
  // AJAX 요청인 경우 JSON 응답
  if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
  }
  
  // 일반 요청인 경우 리다이렉트
  header("Location: community.php");
  exit;
}

// AJAX 댓글 작성 처리
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_id = intval($_POST['post_id']);
  $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
  $content = trim($_POST['content'] ?? '');
  
  // 입력값 검증
  if (!$post_id || !$content) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '모든 필수 항목을 입력해주세요.']);
    exit;
  }
  
  // 게시글 존재 확인
  $stmt = $pdo->prepare("SELECT p.*, u.id as user_id, u.name as user_name FROM community_posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
  $stmt->execute([$post_id]);
  $post = $stmt->fetch();
  
  if (!$post) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '존재하지 않는 게시글입니다.']);
    exit;
  }
  
  // 부모 댓글이 있는 경우 (답글인 경우) 확인
  if ($parent_id) {
    $stmt = $pdo->prepare("SELECT c.*, u.id as user_id, u.name as user_name FROM community_comments c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
    $stmt->execute([$parent_id]);
    $parent_comment = $stmt->fetch();
    
    if (!$parent_comment) {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => '존재하지 않는 댓글입니다.']);
      exit;
    }
    
    // 대댓글인 경우 더 이상의 답글을 허용하지 않음 (2단계 제한)
    if ($parent_comment['parent_id'] !== null) {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => '답글에는 더 이상 답글을 작성할 수 없습니다.']);
      exit;
    }
  }
  
  try {
    $pdo->beginTransaction();
    
    // 댓글 저장
    $stmt = $pdo->prepare("
      INSERT INTO community_comments (post_id, user_id, parent_id, content, created_at) 
      VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$post_id, $_SESSION['user_id'], $parent_id, $content]);
    $comment_id = $pdo->lastInsertId();
    
    // 댓글 정보 조회 (방금 작성한 댓글)
    $stmt = $pdo->prepare("
      SELECT c.*, u.name as user_name, u.profile_img
      FROM community_comments c
      JOIN users u ON c.user_id = u.id
      WHERE c.id = ?
    ");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();
    
    // 알림 생성
    if ($parent_id) {
      // 답글인 경우 원댓글 작성자에게 알림
      if ($parent_comment['user_id'] != $_SESSION['user_id']) {
        $content = $_SESSION['username'] . "님이 회원님의 댓글에 답글을 작성했습니다.";
        create_notification($parent_comment['user_id'], 'reply', $comment_id, $content, $post_id, $_SESSION['user_id']);
      }
    } else {
      // 댓글인 경우 게시글 작성자에게 알림
      if ($post['user_id'] != $_SESSION['user_id']) {
        $content = $_SESSION['username'] . "님이 회원님의 게시글에 댓글을 작성했습니다.";
        create_notification($post['user_id'], 'comment', $comment_id, $content, $post_id, $_SESSION['user_id']);
      }
    }
    
    $pdo->commit();
    
    // 댓글 HTML 생성
    $profile_img = !empty($comment['profile_img']) ? htmlspecialchars($comment['profile_img']) : 'profile_6800f3c5a647f.png';
    $parent_class = $parent_id ? 'comment-reply' : '';
    $parent_style = $parent_id ? 'margin-left: 50px;' : '';
    
    $comment_html = '
      <div class="comment-item ' . $parent_class . '" id="comment-' . $comment_id . '" style="' . $parent_style . '">
        <div class="comment-header">
          <div class="comment-author">
            <img src="./img/profile/' . $profile_img . '" alt="프로필" class="author-img">
            <span>' . htmlspecialchars($comment['user_name']) . '</span>
          </div>
          <div class="comment-meta">
            ' . date('Y-m-d H:i', strtotime($comment['created_at'])) . '
            <div class="comment-actions">
              <button type="button" class="comment-btn" onclick="toggleCommentEdit(' . $comment_id . ')">수정</button>
              <button type="button" class="comment-btn" onclick="deleteComment(' . $comment_id . ', ' . $post_id . ')">삭제</button>
            </div>
          </div>
        </div>
        
        <div class="comment-content" id="comment-content-' . $comment_id . '">
          ' . nl2br(htmlspecialchars($comment['content'])) . '
        </div>
        
        <form class="comment-edit-form" id="comment-edit-form-' . $comment_id . '">
          <input type="hidden" name="comment_id" value="' . $comment_id . '">
          <input type="hidden" name="post_id" value="' . $post_id . '">
          <textarea name="content" class="comment-textarea">' . htmlspecialchars($comment['content']) . '</textarea>
          <div style="text-align: right;">
            <button type="button" class="post-btn list-btn" onclick="toggleCommentEdit(' . $comment_id . ')">취소</button>
            <button type="button" class="comment-submit" onclick="updateComment(' . $comment_id . ', ' . $post_id . ')">수정하기</button>
          </div>
        </form>
        
        ' . ($parent_id === null ? '<button type="button" class="comment-reply-btn" onclick="showReplyForm(' . $comment_id . ')">답글</button>' : '') . '
        
        ' . ($parent_id === null ? '<div class="reply-form-container" id="reply-form-container-' . $comment_id . '"></div>' : '') . '
      </div>
    ';
    
    header('Content-Type: application/json');
    echo json_encode([
      'success' => true, 
      'comment_id' => $comment_id,
      'html' => $comment_html,
      'parent_id' => $parent_id,
      'message' => '댓글이 작성되었습니다.'
    ]);
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '오류가 발생했습니다: ' . $e->getMessage()]);
    exit;
  }
}

// AJAX 댓글 수정 처리
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $comment_id = intval($_POST['comment_id']);
  $post_id = intval($_POST['post_id']);
  $content = trim($_POST['content'] ?? '');
  
  // 입력값 검증
  if (!$comment_id || !$post_id || !$content) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '모든 필수 항목을 입력해주세요.']);
    exit;
  }
  
  // 댓글 정보 및 권한 확인
  $stmt = $pdo->prepare("SELECT * FROM community_comments WHERE id = ?");
  $stmt->execute([$comment_id]);
  $comment = $stmt->fetch();
  
  if (!$comment || $_SESSION['user_id'] != $comment['user_id']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
  }
  
  try {
    // 댓글 업데이트
    $stmt = $pdo->prepare("UPDATE community_comments SET content = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$content, $comment_id]);
    
    header('Content-Type: application/json');
    echo json_encode([
      'success' => true, 
      'comment_id' => $comment_id,
      'content' => nl2br(htmlspecialchars($content)),
      'message' => '댓글이 수정되었습니다.'
    ]);
    exit;
  } catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '오류가 발생했습니다: ' . $e->getMessage()]);
    exit;
  }
}

// AJAX 댓글 삭제 처리
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $comment_id = intval($_POST['comment_id']);
  $post_id = intval($_POST['post_id']);
  
  // 댓글 정보 및 권한 확인
  $stmt = $pdo->prepare("SELECT * FROM community_comments WHERE id = ?");
  $stmt->execute([$comment_id]);
  $comment = $stmt->fetch();
  
  if (!$comment || ($_SESSION['user_id'] != $comment['user_id'] && !is_admin())) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
  }
  
  try {
    // 답글이 있는지 확인
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM community_comments WHERE parent_id = ?");
    $stmt->execute([$comment_id]);
    $reply_count = $stmt->fetchColumn();
    
    if ($reply_count > 0) {
      // 답글이 있는 경우 내용만 삭제된 것으로 표시
      $stmt = $pdo->prepare("UPDATE community_comments SET content = '삭제된 댓글입니다.', updated_at = NOW() WHERE id = ?");
      $stmt->execute([$comment_id]);
      
      $is_deleted = false;
    } else {
      // 답글이 없는 경우 실제 삭제
      $stmt = $pdo->prepare("DELETE FROM community_comments WHERE id = ?");
      $stmt->execute([$comment_id]);
      
      // 알림도 삭제
      $stmt = $pdo->prepare("DELETE FROM notifications WHERE related_id = ? AND (type = 'comment' OR type = 'reply')");
      $stmt->execute([$comment_id]);
      
      $is_deleted = true;
    }
    
    header('Content-Type: application/json');
    echo json_encode([
      'success' => true, 
      'comment_id' => $comment_id,
      'is_deleted' => $is_deleted,
      'message' => '댓글이 삭제되었습니다.'
    ]);
    exit;
  } catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '오류가 발생했습니다: ' . $e->getMessage()]);
    exit;
  }
}

// 일반 요청인 경우 리다이렉트
header("Location: community.php");
exit;