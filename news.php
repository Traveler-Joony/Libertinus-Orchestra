<?php
$page_title='소식';
include 'header.php';
require_once 'config.php';

// 페이지네이션 설정
$items_per_page = 9; // 한 페이지에 표시할 아이템 수
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// 검색 기능 - 제목만 검색 (기본 문자열 이스케이프 처리)
$search = '';
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search = '%' . $pdo->quote(trim($_GET['search'])) . '%';
    $search = str_replace("'", "", $search); // quote()는 작은따옴표를 추가하므로 제거
}

try {
    // 모든 뉴스 항목을 가져오는 기본 쿼리 
    $all_query = "SELECT * FROM news WHERE display = 1";
    
    // 검색어가 있으면 WHERE 조건 추가
    if (!empty($search)) {
        $all_query .= " AND title LIKE '%$search%'";
    }
    
    // 공지사항 먼저 정렬 후 날짜순 정렬
    $all_query .= " ORDER BY main_display DESC, created_at DESC";
    
    // 전체 게시물 수 계산
    $count_query = str_replace("SELECT *", "SELECT COUNT(*)", $all_query);
    // ORDER BY 제거
    $count_query = preg_replace('/ORDER BY.*$/', '', $count_query);
    
    $count_result = $pdo->query($count_query);
    $total_items = $count_result->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);
    
    // 페이지네이션 적용
    $all_query .= " LIMIT $offset, $items_per_page";
    
    // 쿼리 실행
    $all_result = $pdo->query($all_query);
    $all_news = $all_result->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // 에러 발생 시 디버깅용 정보 출력 (실제 서비스에서는 사용자에게 친숙한 오류 메시지로 대체)
    echo '<div class="error">데이터베이스 오류: ' . $e->getMessage() . '</div>';
    echo '<div>쿼리: ' . $all_query . '</div>';
    $all_news = [];
    $total_pages = 0;
}
?>

<style>
/* 소식 페이지 스타일 */
.news-section {
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

.news-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr); /* 한 줄에 3개씩 표시 */
  gap: 2rem;
  margin-bottom: 2rem;
}

.news-card {
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s, box-shadow 0.3s;
  background-color: #fff;
}

.news-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

.news-card a {
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

.news-card:hover .card-image img {
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
  .news-grid {
    grid-template-columns: repeat(2, 1fr); /* 태블릿에서는 2열로 */
  }
}

@media (max-width: 768px) {
  .news-grid {
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
  .news-grid {
    grid-template-columns: 1fr; /* 모바일에서는 1열로 */
    gap: 1rem;
  }
  
  .pagination {
    flex-wrap: wrap;
  }
}
</style>

<main class="container" style="padding-top:clamp(70px,12vh,120px);">
  <section class="news-section">
    <h2 class="section-title">소식</h2>
    
    <!-- 검색 폼 -->
    <div class="search-container">
      <form action="news.php" method="GET" class="search-form">
        <input type="text" name="search" placeholder="제목으로 검색하기" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        <button type="submit" class="search-button">검색</button>
      </form>
    </div>
    
    <!-- 카드형 레이아웃 -->
    <div class="news-grid">
      <?php if (count($all_news) > 0): ?>
        <?php foreach($all_news as $news): ?>
          <div class="news-card">
            <a href="news_detail.php?id=<?= $news['id'] ?>">
              <div class="card-image">
                <?php if (!empty($news['featured_image'])): ?>
                  <img src="../img/news/<?= htmlspecialchars($news['featured_image']) ?>" 
                       alt="<?= htmlspecialchars($news['title']) ?>" 
                       onerror="this.onerror=null; this.src='/img/logo.png';">
                <?php else: ?>
                  <img src="../img/logo.png" alt="기본 이미지">
                <?php endif; ?>
                
                <?php if ($news['main_display'] == 1): ?>
                  <div class="notice-badge">공지</div>
                <?php endif; ?>
              </div>
              
              <div class="card-content">
                <h3 class="card-title"><?= htmlspecialchars($news['title']) ?></h3>
                <p class="card-date"><?= htmlspecialchars(substr($news['created_at'], 0, 10)) ?></p>
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