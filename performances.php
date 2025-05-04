<?php
$page_title='공연';
include 'header.php';
require_once 'config.php';

// 페이지네이션 설정
$items_per_page = 9; // 한 페이지에 표시할 아이템 수
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// 검색 기능 - 제목만 검색
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 공지 게시물 가져오기 (main_display = 1, display = 1)
if (!empty($search)) {
    $sql = "SELECT * FROM performances WHERE main_display = 1 AND display = 1 AND title LIKE ? ORDER BY created_at DESC";
    $notice_stmt = $pdo->prepare($sql);
    $notice_stmt->execute(["%$search%"]);
} else {
    $sql = "SELECT * FROM performances WHERE main_display = 1 AND display = 1 ORDER BY created_at DESC";
    $notice_stmt = $pdo->prepare($sql);
    $notice_stmt->execute();
}
$notice_performances = $notice_stmt->fetchAll(PDO::FETCH_ASSOC);

// 일반 게시물 가져오기 (main_display = 0, display = 1)
// 일반 게시물 수 계산
if (!empty($search)) {
    $sql = "SELECT COUNT(*) FROM performances WHERE main_display = 0 AND display = 1 AND title LIKE ?";
    $count_stmt = $pdo->prepare($sql);
    $count_stmt->execute(["%$search%"]);
} else {
    $sql = "SELECT COUNT(*) FROM performances WHERE main_display = 0 AND display = 1";
    $count_stmt = $pdo->prepare($sql);
    $count_stmt->execute();
}
$total_regular_items = $count_stmt->fetchColumn();

// 노출할 일반 게시물 수 (공지를 제외한 나머지)
$notice_count = count($notice_performances);
$regular_count = $items_per_page - $notice_count;
if ($regular_count <= 0) $regular_count = $items_per_page;

// 일반 게시물 페이지 수 계산
$total_pages = ceil($total_regular_items / $regular_count);

// 일반 게시물 가져오기
if (!empty($search)) {
    $sql = "SELECT * FROM performances WHERE main_display = 0 AND display = 1 AND title LIKE ? ORDER BY created_at DESC LIMIT ?, ?";
    $regular_stmt = $pdo->prepare($sql);
    $regular_stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
    $regular_stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $regular_stmt->bindValue(3, $regular_count, PDO::PARAM_INT);
} else {
    $sql = "SELECT * FROM performances WHERE main_display = 0 AND display = 1 ORDER BY created_at DESC LIMIT ?, ?";
    $regular_stmt = $pdo->prepare($sql);
    $regular_stmt->bindValue(1, $offset, PDO::PARAM_INT);
    $regular_stmt->bindValue(2, $regular_count, PDO::PARAM_INT);
}
$regular_stmt->execute();
$regular_performances = $regular_stmt->fetchAll(PDO::FETCH_ASSOC);

// 모든 공연 합치기
$all_performances = array_merge($notice_performances, $regular_performances);
?>

<style>
/* 공연 페이지 스타일 */
.performance-section {
  padding: 2rem 0;
}

.section-title {
  font-size: 2rem;
  margin-bottom: 2rem;
  text-align: center;
  font-weight: 700;
  color: #333;
}

.error {
  background-color: #ffeeee;
  color: #cc0000;
  padding: 1rem;
  margin-bottom: 1rem;
  border-radius: 4px;
  font-weight: bold;
}

.search-container {
  margin-bottom: 2rem;
  display: flex;
  justify-content: center;
}

.search-form {
  display: flex;
  width: 100%;
  max-width: 500px;
}

.search-form input[type="text"] {
  flex: 1;
  padding: 0.8rem 1rem;
  border: 1px solid #ddd;
  border-radius: 4px 0 0 4px;
  font-size: 1rem;
}

.search-button {
  padding: 0.8rem 1.5rem;
  background-color: #3498db;
  color: white;
  border: none;
  border-radius: 0 4px 4px 0;
  cursor: pointer;
  transition: background-color 0.3s;
}

.search-button:hover {
  background-color: #2980b9;
}

.performance-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr); /* 한 줄에 3개씩 표시 */
  gap: 2rem;
  margin-bottom: 2rem;
}

.performance-card {
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s, box-shadow 0.3s;
  background-color: #fff;
}

.performance-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

.performance-card a {
  text-decoration: none;
  color: inherit;
  display: block;
}

.card-image {
  position: relative;
  height: 200px;
  overflow: hidden;
  background-color: #f5f5f5;
}

.card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
  display: block;
}

.performance-card:hover .card-image img {
  transform: scale(1.05);
}

.notice-badge {
  position: absolute;
  top: 10px;
  left: 10px;
  background-color: rgba(231, 76, 60, 0.9);
  color: white;
  padding: 0.3rem 0.8rem;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: bold;
}

.card-content {
  padding: 1.5rem;
  position: relative;
}

.card-title {
  font-size: 1.25rem;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #333;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-date {
  color: #777;
  font-size: 0.9rem;
}

.no-results {
  grid-column: 1 / -1;
  text-align: center;
  padding: 3rem 0;
  color: #777;
}

/* 페이지네이션 스타일 */
.pagination {
  display: flex;
  justify-content: center;
  margin-top: 2rem;
  gap: 0.5rem;
}

.pagination a {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background-color: #f5f5f5;
  color: #333;
  text-decoration: none;
  border-radius: 4px;
  font-weight: 500;
  transition: background-color 0.3s;
}

.pagination a:hover {
  background-color: #e0e0e0;
}

.pagination a.active {
  background-color: #3498db;
  color: white;
}

.pagination-prev,
.pagination-next {
  width: auto !important;
  padding: 0 1rem;
}

/* 모바일 반응형 */
@media (max-width: 992px) {
  .performance-grid {
    grid-template-columns: repeat(2, 1fr); /* 태블릿에서는 2열로 */
  }
}

@media (max-width: 768px) {
  .performance-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
  }
  
  .card-image {
    height: 180px;
  }
  
  .card-title {
    font-size: 1.1rem;
  }
}

@media (max-width: 480px) {
  .performance-grid {
    grid-template-columns: 1fr; /* 모바일에서는 1열로 */
    gap: 1rem;
  }
  
  .pagination {
    flex-wrap: wrap;
  }
}
</style>

<main class="container" style="padding-top:clamp(70px,12vh,120px);">
  <section class="performance-section">
    <h2 class="section-title">공연 안내</h2>
    
    <!-- 검색 폼 -->
    <div class="search-container">
      <form action="performances.php" method="GET" class="search-form">
        <input type="text" name="search" placeholder="제목으로 검색하기" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        <button type="submit" class="search-button">검색</button>
      </form>
    </div>
    
    <!-- 카드형 레이아웃 -->
    <div class="performance-grid">
      <?php if (count($all_performances) > 0): ?>
        <?php foreach($all_performances as $performance): ?>
          <div class="performance-card">
            <a href="performance_detail.php?id=<?= $performance['id'] ?>">
              <div class="card-image">
                <?php if (!empty($performance['featured_image'])): ?>
                  <img src="/img/performance/<?= htmlspecialchars($performance['featured_image']) ?>" 
                       alt="<?= htmlspecialchars($performance['title']) ?>" 
                       onerror="this.onerror=null; this.src='/img/logo.png';">
                <?php else: ?>
                  <img src="/img/logo.png" alt="기본 이미지">
                <?php endif; ?>
                
                <?php if ($performance['main_display'] == 1): ?>
                  <div class="notice-badge">공지</div>
                <?php endif; ?>
              </div>
              
              <div class="card-content">
                <h3 class="card-title"><?= htmlspecialchars($performance['title']) ?></h3>
                <p class="card-date"><?= htmlspecialchars(substr($performance['created_at'], 0, 10)) ?></p>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-results">
          <p>검색 결과가 없습니다.</p>
        </div>
      <?php endif; ?>
    </div>
    
    <!-- 페이지네이션 -->
    <?php if ($total_pages > 1): ?>
      <div class="pagination">
        <?php if ($current_page > 1): ?>
          <a href="?page=<?= $current_page - 1 ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="pagination-prev">&laquo; 이전</a>
        <?php endif; ?>
        
        <?php
        // 표시할 페이지 범위 계산
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        if ($start_page > 1): ?>
          <a href="?page=1<?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="pagination-link">1</a>
          <?php if ($start_page > 2): ?>
            <span class="pagination-ellipsis">...</span>
          <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
          <a href="?page=<?= $i ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="pagination-link <?= $i == $current_page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        
        <?php if ($end_page < $total_pages): ?>
          <?php if ($end_page < $total_pages - 1): ?>
            <span class="pagination-ellipsis">...</span>
          <?php endif; ?>
          <a href="?page=<?= $total_pages ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="pagination-link"><?= $total_pages ?></a>
        <?php endif; ?>
        
        <?php if ($current_page < $total_pages): ?>
          <a href="?page=<?= $current_page + 1 ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="pagination-next">다음 &raquo;</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php include 'footer.php'; ?>