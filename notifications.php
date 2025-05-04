<?php
$page_title = '알림';
require_once 'config.php';
require_once 'functions.php';

// 로그인 확인
if (!is_logged_in()) {
  echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
  exit;
}

// 페이지네이션 설정
$items_per_page = 20;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $items_per_page;

// 알림 필터링
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where_clause = "WHERE user_id = ?";
$params = [$_SESSION['user_id']];

if ($filter === 'unread') {
  $where_clause .= " AND is_read = 0";
}

// 알림 총 개수 구하기
$count_sql = "SELECT COUNT(*) FROM notifications $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_items = $count_stmt->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

// 알림 목록 가져오기
$notifications_sql = "
  SELECT n.*, p.title as post_title, u.name as sender_name 
  FROM notifications n
  LEFT JOIN community_posts p ON n.post_id = p.id
  LEFT JOIN users u ON n.sender_id = u.id
  $where_clause
  ORDER BY n.created_at DESC
  LIMIT $offset, $items_per_page
";
$notifications_stmt = $pdo->prepare($notifications_sql);
$notifications_stmt->execute($params);
$notifications = $notifications_stmt->fetchAll();

include 'header.php';
?>

<style>
.notifications-section {
  padding: 2rem 0;
  max-width: var(--max-width);
  margin: 0 auto;
  padding-top: calc(clamp(70px, 12vh, 120px) + 2rem);
}

.notifications-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.notifications-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #333;
}

.notifications-actions {
  display: flex;
  gap: 1rem;
}

.notifications-action-btn {
  padding: 0.5rem 1rem;
  background-color: #f8f9fa;
  color: #555;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.notifications-action-btn:hover {
  background-color: #e9ecef;
}

.notifications-action-btn.primary {
  background-color: var(--blue);
  color: white;
  border-color: var(--blue);
}

.notifications-action-btn.primary:hover {
  background-color: #0051d6;
}

.notifications-filter {
  display: flex;
  margin-bottom: 1.5rem;
  border-bottom: 1px solid #eee;
}

.notifications-filter-item {
  padding: 0.75rem 1.5rem;
  cursor: pointer;
  font-weight: 500;
  color: #555;
  transition: all 0.2s;
}

.notifications-filter-item:hover {
  background-color: #f8f9fa;
}

.notifications-filter-item.active {
  color: var(--blue);
  border-bottom: 2px solid var(--blue);
}

.notifications-list {
  margin-bottom: 2rem;
}

.notification-item {
  padding: 1rem;
  border-bottom: 1px solid #eee;
  display: flex;
  align-items: flex-start;
  position: relative;
  transition: background-color 0.2s;
}

.notification-item:hover {
  background-color: #f8f9fa;
}

.notification-item.unread {
  background-color: #f0f7ff;
}

.notification-item.unread:hover {
  background-color: #e1f0ff;
}

.notification-checkbox {
  margin-right: 1rem;
  margin-top: 0.25rem;
}

.notification-content {
  flex: 1;
}

.notification-message {
  font-size: 0.95rem;
  color: #333;
  margin-bottom: 0.3rem;
}

.notification-post {
  font-size: 0.85rem;
  color: #666;
  margin-bottom: 0.3rem;
}

.notification-post a {
  color: var(--blue);
  text-decoration: none;
}

.notification-post a:hover {
  text-decoration: underline;
}

.notification-time {
  font-size: 0.8rem;
  color: #999;
}

.notification-actions {
  margin-left: 1rem;
  display: flex;
  gap: 0.5rem;
}

.notification-action {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f1f3f5;
  color: #666;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.notification-action:hover {
  background-color: #e9ecef;
  color: #333;
}

.notification-action.mark-read:hover {
  color: var(--blue);
}

.notification-action.delete:hover {
  color: #e74c3c;
}

.pagination {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 2rem;
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

.no-notifications {
  padding: 3rem 0;
  text-align: center;
  color: #777;
  font-size: 1rem;
}
</style>

<main class="notifications-section container">
  <div class="notifications-header">
    <h2 class="notifications-title">알림</h2>
    <div class="notifications-actions">
      <button id="markAllReadBtn" class="notifications-action-btn primary">모두 읽음 표시</button>
    </div>
  </div>
  
  <div class="notifications-filter">
    <a href="?filter=all" class="notifications-filter-item <?= $filter === 'all' ? 'active' : '' ?>">전체 알림</a>
    <a href="?filter=unread" class="notifications-filter-item <?= $filter === 'unread' ? 'active' : '' ?>">안 읽은 알림</a>
  </div>
  
  <?php if (count($notifications) > 0): ?>
    <div class="notifications-list">
      <?php foreach ($notifications as $notification): ?>
        <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>" data-id="<?= $notification['id'] ?>">
          <div class="notification-content">
            <div class="notification-message">
              <?= htmlspecialchars($notification['content']) ?>
            </div>
            <?php if ($notification['post_id'] && $notification['post_title']): ?>
              <div class="notification-post">
                게시글: <a href="community_post.php?id=<?= $notification['post_id'] ?>#comment-<?= $notification['related_id'] ?>"><?= htmlspecialchars($notification['post_title']) ?></a>
              </div>
            <?php endif; ?>
            <div class="notification-time">
              <?= date('Y-m-d H:i', strtotime($notification['created_at'])) ?>
              (<?= get_time_ago($notification['created_at']) ?>)
            </div>
          </div>
          <div class="notification-actions">
            <?php if (!$notification['is_read']): ?>
              <div class="notification-action mark-read" title="읽음 표시">
                <i class="fas fa-check"></i>
              </div>
            <?php endif; ?>
            <div class="notification-action delete" title="삭제">
              <i class="fas fa-trash-alt"></i>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    
    <!-- 페이지네이션 -->
    <?php if ($total_pages > 1): ?>
      <div class="pagination">
        <?php if ($current_page > 1): ?>
          <a href="?page=1&filter=<?= $filter ?>">처음</a>
          <a href="?page=<?= $current_page - 1 ?>&filter=<?= $filter ?>">이전</a>
        <?php endif; ?>
        
        <?php
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        if ($start_page > 1): ?>
          <a href="?page=1&filter=<?= $filter ?>">1</a>
          <?php if ($start_page > 2): ?>
            <span class="pagination-ellipsis">...</span>
          <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
          <a href="?page=<?= $i ?>&filter=<?= $filter ?>" class="<?= $i == $current_page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        
        <?php if ($end_page < $total_pages): ?>
          <?php if ($end_page < $total_pages - 1): ?>
            <span class="pagination-ellipsis">...</span>
          <?php endif; ?>
          <a href="?page=<?= $total_pages ?>&filter=<?= $filter ?>"><?= $total_pages ?></a>
        <?php endif; ?>
        
        <?php if ($current_page < $total_pages): ?>
          <a href="?page=<?= $current_page + 1 ?>&filter=<?= $filter ?>">다음</a>
          <a href="?page=<?= $total_pages ?>&filter=<?= $filter ?>">마지막</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <div class="no-notifications">
      알림이 없습니다.
    </div>
  <?php endif; ?>
</main>

<script>
// 알림 읽음 표시
document.querySelectorAll('.notification-action.mark-read').forEach(button => {
  button.addEventListener('click', function() {
    const item = this.closest('.notification-item');
    const id = item.dataset.id;
    
    fetch('notification_process.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `action=mark_read&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        item.classList.remove('unread');
        this.remove(); // 읽음 버튼 제거
      }
    })
    .catch(error => console.error('Error:', error));
  });
});

// 알림 삭제
document.querySelectorAll('.notification-action.delete').forEach(button => {
  button.addEventListener('click', function() {
    if (!confirm('이 알림을 삭제하시겠습니까?')) return;
    
    const item = this.closest('.notification-item');
    const id = item.dataset.id;
    
    fetch('notification_process.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `action=delete&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        item.remove();
      }
    })
    .catch(error => console.error('Error:', error));
  });
});

// 모두 읽음 표시
document.getElementById('markAllReadBtn').addEventListener('click', function() {
  if (!confirm('모든 알림을 읽음으로 표시하시겠습니까?')) return;
  
  fetch('notification_process.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'action=mark_all_read'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      location.reload();
    }
  })
  .catch(error => console.error('Error:', error));
});
</script>

<?php include 'footer.php'; ?>