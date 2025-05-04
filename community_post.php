<?php
require_once 'config.php';
require_once 'functions.php';

// 게시글 ID 확인
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$post_id) {
  header("Location: community.php");
  exit;
}

// 게시글 정보 가져오기
$stmt = $pdo->prepare("
  SELECT p.*, 
         b.name AS board_name, b.id AS board_id, b.admin_only,
         u.name AS user_name, u.profile_img
  FROM community_posts p
  JOIN community_boards b ON p.board_id = b.id
  JOIN users u ON p.user_id = u.id
  WHERE p.id = ?
");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
  header("Location: community.php");
  exit;
}

$page_title = $post['title'] . ' - ' . $post['board_name'];

// 조회수 증가 (중복 방지 세션 활용)
$viewed_posts = isset($_SESSION['viewed_posts']) ? $_SESSION['viewed_posts'] : [];
if (!in_array($post_id, $viewed_posts)) {
  $stmt = $pdo->prepare("UPDATE community_posts SET view_count = view_count + 1 WHERE id = ?");
  $stmt->execute([$post_id]);
  $viewed_posts[] = $post_id;
  $_SESSION['viewed_posts'] = $viewed_posts;
  $post['view_count']++; // 화면에 보이는 조회수도 업데이트
}

// 게시글 이미지 가져오기
$stmt = $pdo->prepare("SELECT * FROM community_post_images WHERE post_id = ? ORDER BY sort_order ASC");
$stmt->execute([$post_id]);
$images = $stmt->fetchAll();

// 게시글 첨부파일 가져오기
$stmt = $pdo->prepare("SELECT * FROM community_post_files WHERE post_id = ? ORDER BY sort_order ASC");
$stmt->execute([$post_id]);
$files = $stmt->fetchAll();

// 댓글 목록 가져오기 (계층형)
$stmt = $pdo->prepare("
  SELECT c.*, 
         u.name AS user_name, u.profile_img
  FROM community_comments c
  JOIN users u ON c.user_id = u.id
  WHERE c.post_id = ? AND c.parent_id IS NULL
  ORDER BY c.created_at ASC
");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();

// 각 댓글의 답글 가져오기
foreach ($comments as $key => $comment) {
  $stmt = $pdo->prepare("
    SELECT c.*, 
           u.name AS user_name, u.profile_img
    FROM community_comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.post_id = ? AND c.parent_id = ?
    ORDER BY c.created_at ASC
  ");
  $stmt->execute([$post_id, $comment['id']]);
  $comments[$key]['replies'] = $stmt->fetchAll();
}

include 'header.php';
?>

<style>
.post-section {
  padding: 2rem 0;
  max-width: 900px;
  margin: 0 auto;
  padding-top: calc(clamp(70px, 12vh, 120px) + 2rem);
}

.post-navigation {
  display: flex;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.post-nav-link {
  text-decoration: none;
  color: #555;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
}

.post-nav-link:hover {
  color: var(--blue);
}

.post-container {
  background: #fff;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
  margin-bottom: 2rem;
}

.post-header {
  padding: 1.5rem;
  border-bottom: 1px solid #eee;
}

.post-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: #333;
  display: flex;
  align-items: center;
}

.post-notice {
  display: inline-block;
  background-color: var(--blue);
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  margin-right: 0.75rem;
}

.post-meta {
  display: flex;
  justify-content: space-between;
  color: #777;
  font-size: 0.9rem;
}

.post-author {
  display: flex;
  align-items: center;
}

.author-img {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  margin-right: 0.75rem;
}

.post-stats {
  display: flex;
  gap: 1rem;
}

.post-content {
  padding: 2rem 1.5rem;
  line-height: 1.7;
  color: #333;
  min-height: 200px;
}

.post-content p {
  margin-bottom: 1rem;
}

.post-images {
  margin-bottom: 2rem;
}

.post-image {
  max-width: 100%;
  margin-bottom: 1rem;
  border-radius: 8px;
}

/* 갤러리 스타일 */
.gallery-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.gallery-item {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  aspect-ratio: 1 / 1;
}

.gallery-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
}

.gallery-item:hover img {
  transform: scale(1.05);
}

/* 첨부파일 스타일 */
.files-container {
  margin-top: 1.5rem;
  border-top: 1px solid #eee;
  padding-top: 1.5rem;
}

.files-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 1rem;