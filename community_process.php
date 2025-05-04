<?php
require_once 'config.php';
require_once 'functions.php';

// 로그인 체크
if (!is_logged_in()) {
  echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
  exit;
}

// 액션 파라미터 확인
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if (!$action) {
  header("Location: community.php");
  exit;
}

// 이미지 디렉토리 확인 및 생성
$upload_dir = __DIR__ . '/img/community/';
if (!file_exists($upload_dir)) {
  mkdir($upload_dir, 0755, true);
}

// 파일 디렉토리 확인 및 생성
$file_dir = __DIR__ . '/files/community/';
if (!file_exists($file_dir)) {
  mkdir($file_dir, 0755, true);
}

// 글 작성 처리
if ($action === 'write' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $board_id = intval($_POST['board_id']);
  $title = trim($_POST['title'] ?? '');
  $content = trim($_POST['content'] ?? '');
  $is_notice = (isset($_POST['is_notice']) && is_admin()) ? 1 : 0;
  
  // 입력값 검증
  if (!$board_id || !$title || !$content) {
    echo "<script>alert('모든 필수 항목을 입력해주세요.'); history.back();</script>";
    exit;
  }
  
  // 게시판 권한 체크
  if (!can_write_post($board_id)) {
    echo "<script>alert('글을 작성할 권한이 없습니다.'); history.back();</script>";
    exit;
  }
  
  try {
    $pdo->beginTransaction();
    
    // 게시글 저장
    $stmt = $pdo->prepare("
      INSERT INTO community_posts (board_id, user_id, title, content, is_notice, created_at) 
      VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$board_id, $_SESSION['user_id'], $title, $content, $is_notice]);
    $post_id = $pdo->lastInsertId();
    
    // 이미지 업로드 처리
    $uploaded_images = 0;
    if (!empty($_FILES['images']['name'][0])) {
      // 최대 5개 이미지까지만 처리
      $max_images = 5;
      $image_count = min(count($_FILES['images']['name']), $max_images);
      
      for ($i = 0; $i < $image_count; $i++) {
        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
          $tmp_name = $_FILES['images']['tmp_name'][$i];
          $name = basename($_FILES['images']['name'][$i]);
          $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
          
          // 이미지 형식 검증
          $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
          if (!in_array($ext, $allowed_exts)) {
            continue;
          }
          
          // 파일 크기 제한 (5MB)
          if ($_FILES['images']['size'][$i] > 5 * 1024 * 1024) {
            continue;
          }
          
          // 파일명 중복 방지를 위한 고유 이름 생성
          $filename = uniqid('community_') . '_' . $i . '.' . $ext;
          $target = $upload_dir . $filename;
          
          // 이미지 업로드
          if (move_uploaded_file($tmp_name, $target)) {
            $stmt = $pdo->prepare("
              INSERT INTO community_post_images (post_id, image_path, sort_order)
              VALUES (?, ?, ?)
            ");
            $stmt->execute([$post_id, $filename, $i]);
            $uploaded_images++;
          }
        }
      }
    }
    
    // 파일 업로드 처리 (관리자만)
    $uploaded_files = 0;
    if (is_admin() && !empty($_FILES['files']['name'][0])) {
      // 최대 5개 파일까지만 처리
      $max_files = 5;
      $file_count = min(count($_FILES['files']['name']), $max_files);
      
      for ($i = 0; $i < $file_count; $i++) {
        if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
          $tmp_name = $_FILES['files']['tmp_name'][$i];
          $original_name = $_FILES['files']['name'][$i];
          $file_size = $_FILES['files']['size'][$i];
          $file_type = $_FILES['files']['type'][$i];
          
          // 파일 크기 제한 (20MB)
          if ($file_size > 20 * 1024 * 1024) {
            continue;
          }
          
          // 파일명 중복 방지를 위한 고유 이름 생성
          $filename = uniqid('file_') . '_' . $i . '_' . sanitize_filename($original_name);
          $target = $file_dir . $filename;
          
          // 파일 업로드
          if (move_uploaded_file($tmp_name, $target)) {
            $stmt = $pdo->prepare("
              INSERT INTO community_post_files (post_id, file_path, original_name, file_size, file_type, sort_order)
              VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$post_id, $filename, $original_name, $file_size, $file_type, $i]);
            $uploaded_files++;
          }
        }
      }
    }
    
    $pdo->commit();
    header("Location: community_post.php?id={$post_id}");
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    echo "<script>alert('오류가 발생했습니다: " . $e->getMessage() . "'); history.back();</script>";
    exit;
  }
}

// 글 수정 처리
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_id = intval($_POST['id']);
  $title = trim($_POST['title'] ?? '');
  $content = trim($_POST['content'] ?? '');
  $is_notice = (isset($_POST['is_notice']) && is_admin()) ? 1 : 0;
  
  // 입력값 검증
  if (!$post_id || !$title || !$content) {
    echo "<script>alert('모든 필수 항목을 입력해주세요.'); history.back();</script>";
    exit;
  }
  
  // 게시글 정보 및 권한 확인
  $stmt = $pdo->prepare("SELECT * FROM community_posts WHERE id = ?");
  $stmt->execute([$post_id]);
  $post = $stmt->fetch();
  
  if (!$post || ($_SESSION['user_id'] != $post['user_id'] && !is_admin())) {
    echo "<script>alert('권한이 없습니다.'); history.back();</script>";
    exit;
  }
  
  try {
    $pdo->beginTransaction();
    
    // 게시글 업데이트
    if (is_admin()) {
      $stmt = $pdo->prepare("UPDATE community_posts SET title = ?, content = ?, is_notice = ? WHERE id = ?");
      $stmt->execute([$title, $content, $is_notice, $post_id]);
    } else {
      $stmt = $pdo->prepare("UPDATE community_posts SET title = ?, content = ? WHERE id = ?");
      $stmt->execute([$title, $content, $post_id]);
    }
    
    // 삭제할 이미지 처리
    if (!empty($_POST['remove_images'])) {
      foreach ($_POST['remove_images'] as $image_id) {
        $stmt = $pdo->prepare("SELECT image_path FROM community_post_images WHERE id = ? AND post_id = ?");
        $stmt->execute([$image_id, $post_id]);
        $image = $stmt->fetch();
        
        if ($image) {
          // 이미지 파일 삭제 (실패해도 계속 진행)
          $image_path = $upload_dir . $image['image_path'];
          if (file_exists($image_path)) {
            @unlink($image_path);
          }
          
          // DB에서 이미지 정보 삭제
          $stmt = $pdo->prepare("DELETE FROM community_post_images WHERE id = ?");
          $stmt->execute([$image_id]);
        }
      }
    }
    
    // 삭제할 파일 처리
    if (!empty($_POST['remove_files'])) {
      foreach ($_POST['remove_files'] as $file_id) {
        $stmt = $pdo->prepare("SELECT file_path FROM community_post_files WHERE id = ? AND post_id = ?");
        $stmt->execute([$file_id, $post_id]);
        $file = $stmt->fetch();
        
        if ($file) {
          // 파일 삭제 (실패해도 계속 진행)
          $file_path = $file_dir . $file['file_path'];
          if (file_exists($file_path)) {
            @unlink($file_path);
          }
          
          // DB에서 파일 정보 삭제
          $stmt = $pdo->prepare("DELETE FROM community_post_files WHERE id = ?");
          $stmt->execute([$file_id]);
        }
      }
    }
    
    // 이미지 업로드 처리
    if (!empty($_FILES['images']['name'][0])) {
      // 현재 이미지 수 확인
      $stmt = $pdo->prepare("SELECT COUNT(*) FROM community_post_images WHERE post_id = ?");
      $stmt->execute([$post_id]);
      $current_image_count = $stmt->fetchColumn();
      
      // 남은 이미지 슬롯 계산
      $remaining_slots = 5 - $current_image_count;
      if ($remaining_slots > 0) {
        $image_count = min(count($_FILES['images']['name']), $remaining_slots);
        
        // 정렬 순서 결정을 위해 현재 최대 순서 가져오기
        $stmt = $pdo->prepare("SELECT MAX(sort_order) FROM community_post_images WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $max_sort_order = $stmt->fetchColumn() ?: -1;
        
        for ($i = 0; $i < $image_count; $i++) {
          if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['images']['tmp_name'][$i];
            $name = basename($_FILES['images']['name'][$i]);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            
            // 이미지 형식 검증
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowed_exts)) {
              continue;
            }
            
            // 파일 크기 제한 (5MB)
            if ($_FILES['images']['size'][$i] > 5 * 1024 * 1024) {
              continue;
            }
            
            // 파일명 중복 방지를 위한 고유 이름 생성
            $filename = uniqid('community_') . '_' . $i . '.' . $ext;
            $target = $upload_dir . $filename;
            
            // 이미지 업로드
            if (move_uploaded_file($tmp_name, $target)) {
              $stmt = $pdo->prepare("
                INSERT INTO community_post_images (post_id, image_path, sort_order)
                VALUES (?, ?, ?)
              ");
              $stmt->execute([$post_id, $filename, $max_sort_order + $i + 1]);
            }
          }
        }
      }
    }
    
    // 파일 업로드 처리 (관리자만)
    if (is_admin() && !empty($_FILES['files']['name'][0])) {
      // 현재 파일 수 확인
      $stmt = $pdo->prepare("SELECT COUNT(*) FROM community_post_files WHERE post_id = ?");
      $stmt->execute([$post_id]);
      $current_file_count = $stmt->fetchColumn();
      
      // 남은 파일 슬롯 계산
      $remaining_slots = 5 - $current_file_count;
      if ($remaining_slots > 0) {
        $file_count = min(count($_FILES['files']['name']), $remaining_slots);
        
        // 정렬 순서 결정을 위해 현재 최대 순서 가져오기
        $stmt = $pdo->prepare("SELECT MAX(sort_order) FROM community_post_files WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $max_sort_order = $stmt->fetchColumn() ?: -1;
        
        for ($i = 0; $i < $file_count; $i++) {
          if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['files']['tmp_name'][$i];
            $original_name = $_FILES['files']['name'][$i];
            $file_size = $_FILES['files']['size'][$i];
            $file_type = $_FILES['files']['type'][$i];
            
            // 파일 크기 제한 (20MB)
            if ($file_size > 20 * 1024 * 1024) {
              continue;
            }
            
            // 파일명 중복 방지를 위한 고유 이름 생성
            $filename = uniqid('file_') . '_' . $i . '_' . sanitize_filename($original_name);
            $target = $file_dir . $filename;
            
            // 파일 업로드
            if (move_uploaded_file($tmp_name, $target)) {
              $stmt = $pdo->prepare("
                INSERT INTO community_post_files (post_id, file_path, original_name, file_size, file_type, sort_order)
                VALUES (?, ?, ?, ?, ?, ?)
              ");
              $stmt->execute([$post_id, $filename, $original_name, $file_size, $file_type, $max_sort_order + $i + 1]);
            }
          }
        }
      }
    }
    
    $pdo->commit();
    header("Location: community_post.php?id={$post_id}");
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    echo "<script>alert('오류가 발생했습니다: " . $e->getMessage() . "'); history.back();</script>";
    exit;
  }
}

// 글 삭제 처리
if ($action === 'delete' && isset($_GET['id'])) {
  $post_id = intval($_GET['id']);
  
  // 게시글 정보 및 권한 확인
  $stmt = $pdo->prepare("SELECT * FROM community_posts WHERE id = ?");
  $stmt->execute([$post_id]);
  $post = $stmt->fetch();
  
  if (!$post || ($_SESSION['user_id'] != $post['user_id'] && !is_admin())) {
    echo "<script>alert('권한이 없습니다.'); history.back();</script>";
    exit;
  }
  
  try {
    $pdo->beginTransaction();
    
    // 게시글 이미지 정보 조회
    $stmt = $pdo->prepare("SELECT image_path FROM community_post_images WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 게시글 파일 정보 조회
    $stmt = $pdo->prepare("SELECT file_path FROM community_post_files WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $files = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 이미지 파일 삭제 (실패해도 계속 진행)
    foreach ($images as $image_path) {
      $image_file = $upload_dir . $image_path;
      if (file_exists($image_file)) {
        @unlink($image_file);
      }
    }
    
    // 첨부 파일 삭제 (실패해도 계속 진행)
    foreach ($files as $file_path) {
      $file = $file_dir . $file_path;
      if (file_exists($file)) {
        @unlink($file);
      }
    }
    
    // 알림 삭제
    $pdo->prepare("DELETE FROM notifications WHERE post_id = ?")->execute([$post_id]);
    
    // 게시글과 관련된 모든 데이터 삭제
    // 참고: 외래키 제약조건이 CASCADE로 설정되어 있으면 아래 쿼리는 불필요할 수 있음
    $pdo->prepare("DELETE FROM community_comments WHERE post_id = ?")->execute([$post_id]);
    $pdo->prepare("DELETE FROM community_post_images WHERE post_id = ?")->execute([$post_id]);
    $pdo->prepare("DELETE FROM community_post_files WHERE post_id = ?")->execute([$post_id]);
    $pdo->prepare("DELETE FROM community_posts WHERE id = ?")->execute([$post_id]);
    
    $pdo->commit();
    
    $board_id = $post['board_id'];
    header("Location: community_board.php?id={$board_id}");
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    echo "<script>alert('오류가 발생했습니다: " . $e->getMessage() . "'); history.back();</script>";
    exit;
  }
}

/**
 * 파일명 안전하게 처리 (특수문자 제거 등)
 */
function sanitize_filename($filename) {
  // 한글 및 영문자, 숫자, 일부 특수문자만 허용
  $filename = preg_replace('/[^\pL\pN\s\-_.]/u', '', $filename);
  // 공백을 언더스코어로 대체
  $filename = str_replace(' ', '_', $filename);
  // 중복된 언더스코어 제거
  $filename = preg_replace('/_+/', '_', $filename);
  // 파일명이 비어있으면 기본값 설정
  if (empty($filename)) {
    $filename = 'file_' . time();
  }
  
  return $filename;
}

// 잘못된 요청 처리
header("Location: community.php");
exit;