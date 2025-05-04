<?php
$page_title = '커뮤니티';
require_once 'config.php';
require_once 'functions.php';
include 'header.php';

// 게시판 목록 조회
$boards = $pdo->query("SELECT * FROM community_boards ORDER BY sort_order ASC, id ASC")->fetchAll();

// 각 게시판의 최신 게시글 5개씩 가져오기
$recent_posts = [];
foreach ($boards as $board) {
  $stmt = $pdo->prepare("
    SELECT p.*, 
           b.name AS board_name, 
           u.name AS user_name,
           u.profile_img,
           (SELECT COUNT(*) FROM community_comments WHERE post_id = p.id) AS comment_count
    FROM community_posts p
    JOIN community_boards b ON p.board_id = b.id
    JOIN users u ON p.user_id = u.id
    WHERE p.board_id = ?
    ORDER BY p.is_notice DESC, p.created_at DESC
    LIMIT 5
  ");
  $stmt->execute([$board['id']]);
  $recent_posts[$board['id']] = $stmt->fetchAll();
}
?>

<style>
.community-section {
  padding: 2rem 0;
  max-width: var(--max-width);
  margin: 0 auto;
  padding-top: calc(clamp(70px, 12vh, 120px) + 2rem);
}

.community-header {
  text-align: center;
  margin-bottom: 3rem;
}

.community-title {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: #333;
}

.community-description {
  color: #666;
  max-width: 700px;
  margin: 0 auto;
  line-height: 1.6;
}

.board-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 2rem;
  border-bottom: 1px solid #eee;
  padding-bottom: 1rem;
}

.board-tab {
  padding: 0.7rem 1.2rem;
  background-color: #f5f5f5;
  border-radius: 30px;
  font-weight: 600;
  color: #555;
  text-decoration: none;
  transition: all 0.2s;
}

.board-tab:hover {
  background-color: #e6e6e6;
  color: #333;
}

.board-section {
  background: #fff;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.board-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid var(--blue);
}

.board-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: #333;
}

.view-all {
  color: var(--blue);
  font-size: 0.9rem;
  text-decoration: none;
  display: flex;
  align-items: center;
}

.view-all:hover {
  text-decoration: underline;
}

.post-list {
  list-style: none;
}

.post-item {
  padding: 1rem 0;
  border-bottom: 1px solid #eee;
}

.post-item:last-child {
  border-bottom: none;
}

.post-link {
  display: flex;
  justify-content: space-between;
  align-items: center;
  text-decoration: none;
  color: #333;
}

.post-info {
  display: flex;
  flex-direction: column;
}

.post-title {
  font-weight: 600;
  margin-bottom: 0.3rem;
  display: flex;
  align-items: center;
}

.post-meta {
  display: flex;
  align-items: center;
  font-size: 0.85rem;
  color: #777;
}

.post-notice {
  display: inline-block;
  background-color: var(--blue);
  color: white;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  margin-right: 0.75rem;
}

.post-author {
  display: flex;
  align-items: center;
  margin-right: 1rem;
}

.author-img {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  object-fit: cover;
  margin-right: 0.5rem;
}

.post-date {
  margin-right: 1rem;
}

.comment-count {
  color: var(--blue);
  font-weight: 600;
}

.post-stats {
  text-align: right;
  font-size: 0.85rem;
  color: #777;
}

.empty-posts {
  padding: 2rem 0;
  text-align: center;
  color: #777;
}

.login-message {
  background-color: #f8f9fa;
  padding: 1.5rem;
  text-align: center;
  border-radius: var(--radius);
  margin-top: 2rem;
}

.login-message p {
  margin-bottom: 1rem;
  color: #555;
}

.login-button {
  display: inline-block;
  background-color: var(--blue);
  color: white;
  padding: 0.7rem 1.5rem;
  border-radius: 4px;
  text-decoration: none;
  font-weight: 600;
  transition: background-color 0.2s;
}

.login-button:hover {
  background-color: #0051d6;
}

@media (max-width: 768px) {
  .post-link {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .post-stats {
    text-align: left;
    margin-top: 0.5rem;
  }
  
  .post-meta {
    flex-wrap: wrap;
    margin-bottom: 0.3rem;
  }
  
  .post-author, .post-date {
    margin-bottom: 0.3rem;
  }
}
</style>

<main class="community-section container">
  <div class="community-header">
    <h2 class="community-title">커뮤니티</h2>
    <p class="community-description">리버티노 오케스트라 회원들과 자유롭게 소통하는 공간입니다. 음악에 대한 이야기부터 일상까지, 다양한 주제로 이야기를 나눠보세요.</p>
  </div>
  
  <!-- 게시판 탭 -->
  <div class="board-tabs">
    <?php foreach ($boards as $board): ?>
      <a href="community_board.php?id=<?= $board['id'] ?>" class="board-tab"><?= htmlspecialchars($board['name']) ?></a>
    <?php endforeach; ?>
  </div>
  
  <!-- 각 게시판별 최신 게시글 -->
  <?php foreach ($boards as $board): ?>
    <section class="board-section">
      <div class="board-header">
        <h3 class="board-name"><?= htmlspecialchars($board['name']) ?></h3>
        <a href="community_board.php?id=<?= $board['id'] ?>" class="view-all">전체보기 &rarr;</a>
      </div>
      
      <?php if (!empty($recent_posts[$board['id']])): ?>
        <ul class="post-list">
          <?php foreach ($recent_posts[$board['id']] as $post): ?>
            <li class="post-item">
              <a href="community_post.php?id=<?= $post['id'] ?>" class="post-link">
                <div class="post-info">
                  <div class="post-title">
                    <?php if ($post['is_notice']): ?>
                      <span class="post-notice">공지</span>
                    <?php endif; ?>
                    <?= htmlspecialchars($post['title']) ?>
                    <?php if ($post['comment_count'] > 0): ?>
                      <span class="comment-count">[<?= $post['comment_count'] ?>]</span>
                    <?php endif; ?>
                  </div>
                  <div class="post-meta">
                    <div class="post-author">
                      <img src="./img/profile/<?= !empty($post['profile_img']) ? htmlspecialchars($post['profile_img']) : 'profile_6800f3c5a647f.png' ?>" alt="프로필" class="author-img">
                      <?= htmlspecialchars($post['user_name']) ?>
                    </div>
                    <span class="post-date"><?= date('Y-m-d', strtotime($post['created_at'])) ?></span>
                  </div>
                </div>
                <div class="post-stats">
                  <span>조회 <?= number_format($post['view_count']) ?></span>
                </div>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="empty-posts">
          <p>등록된 게시글이 없습니다.</p>
        </div>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
  
  <!-- 비로그인 사용자에게 로그인 유도 -->
  <?php if (!is_logged_in()): ?>
    <div class="login-message">
      <p>게시글 작성 및 댓글 기능은 로그인 후 이용하실 수 있습니다.</p>
      <a href="login.php" class="login-button">로그인하기</a>
    </div>
  <?php endif; ?>
</main>

<?php include 'footer.php'; ?>