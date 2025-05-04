<?php
require_once 'config.php';
require_once 'functions.php';

// 게시판 ID 확인
$board_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$board_id) {
  header("Location: community.php");
  exit;
}

// 게시판 정보 가져오기
$stmt = $pdo->prepare("SELECT * FROM community_boards WHERE id = ?");
$stmt->execute([$board_id]);
$board = $stmt->fetch();

if (!$board) {
  header("Location: community.php");
  exit;
}

$page_title = $board['name'] . ' - 커뮤니티';

// 페이지네이션 설정
$items_per_page = 15;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $items_per_page;

// 검색 기능
$search_type = isset($_GET['search_type']) ? $_GET['search_type'] : '';
$search_keyword = isset($_GET['search_keyword']) ? trim($_GET['search_keyword']) : '';

// 검색 및 게시글 조회 쿼리 구성
$where_conditions = ["p.board_id = ?"];
$params = [$board_id];

if ($search_keyword) {
  switch ($search_type) {
    case 'title':
      $where_conditions[] = "p.title LIKE ?";
      $params[] = "%$search_keyword%";
      break;
    case 'content':
      $where_conditions[] = "p.content LIKE ?";
      $params[] = "%$search_keyword%";
      break;
    case 'writer':
      $where_conditions[] = "u.name LIKE ?";
      $params[] = "%$search_keyword%";
      break;
    case 'title_content':
      $where_conditions[] = "(p.title LIKE ? OR p.content LIKE ?)";
      $params[] = "%$search_keyword%";
      $params[] = "%$search_keyword%";
      break;
    default:
      $search_type = 'title_content';
      $where_conditions[] = "(p.title LIKE ? OR p.content LIKE ?)";
      $params[] = "%$search_keyword%";
      $params[] = "%$search_keyword%";
  }
}

$where_clause = implode(" AND ", $where_conditions);

// 게시글 총 개수 구하기
$count_sql = "
  SELECT COUNT(*) FROM community_posts p
  JOIN users u ON p.user_id = u.id
  WHERE $where_clause
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_items = $count_stmt->fetchColumn();

$total_pages = ceil($total_items / $items_per_page);

// 게시글 목록 가져오기
$post_sql = "
  SELECT p.*, 
         u.name AS user_name,
         u.profile_img,
         (SELECT COUNT(*) FROM community_comments WHERE post_id = p.id) AS comment_count
  FROM community_posts p
  JOIN users u ON p.user_id = u.id
  WHERE $where_clause
  ORDER BY p.is_notice DESC, p.created_at DESC
  LIMIT $offset, $items_per_page
";
$post_stmt = $pdo->prepare($post_sql);
$post_stmt->execute($params);
$posts = $post_stmt->fetchAll();

include 'header.php';
?>

<style>
.board-section {
  padding: 2rem 0;
  max-width: var(--max-width);
  margin: 0 auto;
  padding-top: calc(clamp(70px, 12vh, 120px) + 2rem);
}

.board-header {
  text-align: center;
  margin-bottom: 2rem;
}

.board-title {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.75rem;
  color: #333;
}

.board-description {
  color: #666;
  margin-bottom: 2rem;
}

.board-content {
  background: #fff;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
  margin-bottom: 2rem;
}

.post-table {
  width: 100%;
  border-collapse: collapse;
}

.post-table th,
.post-table td {
  padding: 1rem;
  border-bottom: 1px solid #eee;
  text-align: center;
}

.post-table th {
  font-weight: 600;
  color: #333;
  background-color: #f8f9fa;
}

.post-table td {
  color: #555;
}

.post-table tr:last-child td {
  border-bottom: none;
}

.post-title-cell {
  text-align: left;
}

.post-title-link {
  text-decoration: none;
  color: #333;
  font-weight: 500;
  transition: color 0.2s;
  display: flex;
  align-items: center;
}

.post-title-link:hover {
  color: var(--blue);
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

.comment-badge {
  display: inline-block;
  color: var(--blue);
  font-weight: 600;
  margin-left: 0.5rem;
}

.author-profile {
  display: flex;
  align-items: center;
  justify-content: center;
}

.author-img {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  object-fit: cover;
  margin-right: 0.5rem;
}

.empty-posts {
  padding: 3rem 0;
  text-align: center;
  color: #777;
}

.board-actions {
  display: flex;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.board-nav {
  display: flex;
  list-style: none;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.board-nav-item {
  padding: 0.6rem 1.2rem;
  background-color: #f5f5f5;
  border-radius: 30px;
  font-weight: 600;
  color: #555;
  text-decoration: none;
  transition: all 0.2s;
}

.board-nav-item:hover {
  background-color: #e6e6e6;
  color: #333;
}

.board-nav-item.active {
  background-color: var(--blue);
  color: white;
}

.write-btn {
  display: inline-block;
  background-color: var(--blue);
  color: white;
  padding: 0.7rem 1.5rem;
  border-radius: 4px;
  text-decoration: none;
  font-weight: 600;
  transition: background-color 0.2s;
}

.write-btn:hover {
  background-color: #0051d6;
}

.search-form {
  display: flex;
  gap: 0.5rem;
  margin-top: 1.5rem;
}

.search-select {
  padding: 0.6rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  color: #555;
}

.search-input {
  flex: 1;
  padding: 0.6rem 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.search-btn {
  padding: 0.6rem 1.2rem;
  background-color: #6c757d;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
}

.search-btn:hover {
  background-color: #5a6268;
}

.pagination {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin: 2rem 0;
}

.pagination a {
  padding: 0.5rem 0.8rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  color: #555;
  text-decoration: none;
  transition: all 0.2s;
}

.pagination a:hover {
  background-color: #f8f9fa;
}

.pagination a.active {
  background-color: var(--blue);
  color: white;
  border-color: var(--blue);
}

.pagination-ellipsis {
  display: flex;
  align-items: center;
  padding: 0 0.5rem;
  color: #777;
}

/* 반응형 스타일 */
@media (max-width: 768px) {
  .board-actions {
    flex-direction: column;
    gap: 1rem;
  }
  
  .post-table th:nth-child(3),
  .post-table th:nth-child(4),
  .post-table td:nth-child(3),
  .post-table td:nth-child(4) {
    display: none;
  }
  
  .search-form {
    flex-wrap: wrap;
  }
  
  .search-select {
    width: 30%;
  }
  
  .search-input {
    width: calc(70% - 0.5rem);
  }
  
  .search-btn {
    width: 100%;
    margin-top: 0.5rem;
  }
}

@media (max-width: 480px) {
  .post-table th:nth-child(5),
  .post-table td:nth-child(5) {
    display: none;
  }
  
  .board-nav {
    justify-content: center;
  }
}
</style>

<main class="board-section container">
  <div class="board-header">
    <h2 class="board-title"><?= htmlspecialchars($board['name']) ?></h2>
    <p class="board-description"><?= htmlspecialchars($board['description'] ?? '') ?></p>
    
    <!-- 게시판 네비게이션 -->
    <ul class="board-nav">
      <?php
      $stmt = $pdo->query("SELECT * FROM community_boards ORDER BY sort_order ASC, id ASC");
      $all_boards = $stmt->fetchAll();
      foreach ($all_boards as $b) {
        $active_class = ($b['id'] == $board_id) ? 'active' : '';
        echo '<li><a href="community_board.php?id=' . $b['id'] . '" class="board-nav-item ' . $active_class . '">' . htmlspecialchars($b['name']) . '</a></li>';
      }
      ?>
    </ul>
  </div>
  
  <div class="board-actions">
    <div></div>
    <?php if (is_logged_in()): ?>
      <a href="community_write.php?board_id=<?= $board_id ?>" class="write-btn">글쓰기</a>
    <?php endif; ?>
  </div>
  
  <div class="board-content">
    <table class="post-table">
      <thead>
        <tr>
          <th style="width: 8%;">번호</th>
          <th>제목</th>
          <th style="width: 12%;">작성자</th>
          <th style="width: 12%;">작성일</th>
          <th style="width: 8%;">조회</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($posts) > 0): ?>
          <?php foreach ($posts as $post): ?>
            <tr>
              <td>
                <?php if ($post['is_notice']): ?>
                  <span class="post-notice">공지</span>
                <?php else: ?>
                  <?= number_format($post['id']) ?>
                <?php endif; ?>
              </td>
              <td class="post-title-cell">
                <a href="community_post.php?id=<?= $post['id'] ?>" class="post-title-link">
                  <?= htmlspecialchars($post['title']) ?>
                  <?php if ($post['comment_count'] > 0): ?>
                    <span class="comment-badge">[<?= $post['comment_count'] ?>]</span>
                  <?php endif; ?>
                </a>
              </td>
              <td>
                <div class="author-profile">
                  <img src="./img/profile/<?= !empty($post['profile_img']) ? htmlspecialchars($post['profile_img']) : 'profile_6800f3c5a647f.png' ?>" alt="프로필" class="author-img">
                  <?= htmlspecialchars($post['user_name']) ?>
                </div>
              </td>
              <td><?= date('Y-m-d', strtotime($post['created_at'])) ?></td>
              <td><?= number_format($post['view_count']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="empty-posts">
              <?php if ($search_keyword): ?>
                검색 결과가 없습니다.
              <?php else: ?>
                등록된 게시글이 없습니다.
              <?php endif; ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <!-- 검색 폼 -->
  <form action="community_board.php" method="get" class="search-form">
    <input type="hidden" name="id" value="<?= $board_id ?>">
    <select name="search_type" class="search-select">
      <option value="title_content" <?= $search_type == 'title_content' ? 'selected' : '' ?>>제목+내용</option>
      <option value="title" <?= $search_type == 'title' ? 'selected' : '' ?>>제목</option>
      <option value="content" <?= $search_type == 'content' ? 'selected' : '' ?>>내용</option>
      <option value="writer" <?= $search_type == 'writer' ? 'selected' : '' ?>>작성자</option>
    </select>
    <input type="text" name="search_keyword" class="search-input" value="<?= htmlspecialchars($search_keyword) ?>" placeholder="검색어를 입력하세요">
    <button type="submit" class="search-btn">검색</button>
  </form>
  
  <!-- 페이지네이션 -->
  <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php if ($current_page > 1): ?>
        <a href="?id=<?= $board_id ?>&page=1<?= $search_keyword ? '&search_type=' . $search_type . '&search_keyword=' . urlencode($search_keyword) : '' ?>">처음</a>
        <a href="?id=<?= $board_id ?>&page=<?= $current_page - 1 ?><?= $search_keyword ? '&search_type=' . $search_type . '&search_keyword=' . urlencode($search_keyword) : '' ?>">이전</a>
      <?php endif; ?>
      
      <?php
      $start_page = max(1, $current_page - 2);
      $end_page = min($total_pages, $current_page + 2);
      
      if ($start_page > 1): ?>
        <a href="?id=<?= $board_id ?>&page=1<?= $search_keyword ? '&search_type=' . $search_type . '&search_keyword=' . urlencode($search_keyword) : '' ?>">1</a>
        <?php if ($start_page > 2): ?>
          <span class="pagination-ellipsis">...</span>
        <?php endif; ?>
      <?php endif; ?>
      
      <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
        <a href="?id=<?= $board_id ?>&page=<?= $i ?><?= $search_keyword ? '&search_type=' . $search_type . '&search_keyword=' . urlencode($search_keyword) : '' ?>" class="<?= $i == $current_page ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
      
      <?php if ($end_page < $total_pages): ?>
        <?php if ($end_page < $total_pages - 1): ?>
          <span class="pagination-ellipsis">...</span>
        <?php endif; ?>
        <a href="?id=<?= $board_id ?>&page=<?= $total_pages ?><?= $search_keyword ? '&search_type=' . $search_type . '&search_keyword=' . urlencode($search_keyword) : '' ?>"><?= $total_pages ?></a>
      <?php endif; ?>
      
      <?php if ($current_page < $total_pages): ?>
        <a href="?id=<?= $board_id ?>&page=<?= $current_page + 1 ?><?= $search_keyword ? '&search_type=' . $search_type . '&search_keyword=' . urlencode($search_keyword) : '' ?>">다음</a>
        <a href="?id=<?= $board_id ?>&page=<?= $total_pages ?><?= $search_keyword ? '&search_type=' . $search_type . '&search_keyword=' . urlencode($search_keyword) : '' ?>">마지막</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</main>

<?php include 'footer.php'; ?>