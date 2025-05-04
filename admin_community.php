<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// 관리자 권한 체크
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
  echo "<main class='login-wrap'><div class='login-box'><p>⚠️ 관리자 권한이 없습니다.</p></div></main>";
  require_once 'footer.php';
  exit;
}

$page_title = '커뮤니티 관리';
require_once 'header.php';

// 게시판 추가 처리
if (isset($_POST['add_board'])) {
  $name = trim($_POST['name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $sort_order = intval($_POST['sort_order'] ?? 0);
  
  if ($name) {
    $stmt = $pdo->prepare("INSERT INTO community_boards (name, description, sort_order) VALUES (?, ?, ?)");
    $stmt->execute([$name, $description, $sort_order]);
    $success_message = "게시판이 추가되었습니다.";
  } else {
    $error_message = "게시판 이름을 입력해주세요.";
  }
}

// 게시판 수정 처리
if (isset($_POST['edit_board'])) {
  $board_id = intval($_POST['board_id']);
  $name = trim($_POST['name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $sort_order = intval($_POST['sort_order'] ?? 0);
  
  if ($name && $board_id) {
    $stmt = $pdo->prepare("UPDATE community_boards SET name = ?, description = ?, sort_order = ? WHERE id = ?");
    $stmt->execute([$name, $description, $sort_order, $board_id]);
    $success_message = "게시판이 수정되었습니다.";
  } else {
    $error_message = "게시판 이름을 입력해주세요.";
  }
}

// 게시판 삭제 처리
if (isset($_GET['delete_board']) && is_numeric($_GET['delete_board'])) {
  $board_id = intval($_GET['delete_board']);
  
  // 게시판에 게시글이 있는지 확인
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM community_posts WHERE board_id = ?");
  $stmt->execute([$board_id]);
  $post_count = $stmt->fetchColumn();
  
  if ($post_count > 0) {
    $error_message = "이 게시판에는 게시글이 존재합니다. 게시글을 먼저 삭제해주세요.";
  } else {
    $stmt = $pdo->prepare("DELETE FROM community_boards WHERE id = ?");
    $stmt->execute([$board_id]);
    $success_message = "게시판이 삭제되었습니다.";
  }
}

// 게시판 목록 조회
$boards = $pdo->query("SELECT * FROM community_boards ORDER BY sort_order ASC, id ASC")->fetchAll();

// 최근 게시글 조회
$recent_posts = $pdo->query("
  SELECT p.*, 
         b.name AS board_name, 
         u.name AS user_name
  FROM community_posts p
  JOIN community_boards b ON p.board_id = b.id
  JOIN users u ON p.user_id = u.id
  ORDER BY p.created_at DESC
  LIMIT 20
")->fetchAll();
?>

<style>
.admin-community {
  padding: 2rem 0;
  max-width: var(--max-width);
  margin: 0 auto;
  padding-top: calc(clamp(70px, 12vh, 120px) + 2rem);
}

.admin-title {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 2rem;
  color: #333;
}

.admin-section {
  background: #fff;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.admin-section-title {
  font-size: 1.35rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid var(--blue);
  color: #333;
}

.board-form {
  margin-bottom: 2rem;
}

.form-row {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1rem;
  align-items: flex-end;
}

.form-group {
  flex: 1;
  min-width: 200px;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #555;
  font-size: 0.95rem;
}

.form-input {
  width: 100%;
  padding: 0.7rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.95rem;
}

.form-group-small {
  flex: 0 0 100px;
}

.form-submit {
  padding: 0.7rem 1.5rem;
  background-color: var(--blue);
  color: white;
  border: none;
  border-radius: 4px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.form-submit:hover {
  background-color: #0051d6;
}

.alert {
  padding: 1rem;
  border-radius: 4px;
  margin-bottom: 1.5rem;
}

.alert-success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert-danger {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.board-table,
.post-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 1rem;
}

.board-table th,
.board-table td,
.post-table th,
.post-table td {
  padding: 0.75rem;
  border-bottom: 1px solid #eee;
  text-align: left;
}

.board-table th,
.post-table th {
  font-weight: 600;
  color: #333;
  background-color: #f8f9fa;
}

.board-table tbody tr:hover,
.post-table tbody tr:hover {
  background-color: #f8f9fa;
}

.board-actions,
.post-actions {
  display: flex;
  gap: 0.5rem;
}

.board-actions a,
.post-actions a {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  text-decoration: none;
  font-size: 0.85rem;
  font-weight: 500;
  transition: background-color 0.2s;
}

.edit-link {
  background-color: #6c757d;
  color: white;
}

.edit-link:hover {
  background-color: #5a6268;
}

.delete-link {
  background-color: #dc3545;
  color: white;
}

.delete-link:hover {
  background-color: #c82333;
}

.view-link {
  background-color: #28a745;
  color: white;
}

.view-link:hover {
  background-color: #218838;
}

/* 모달 스타일 */
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.4);
  overflow: auto;
}

.modal-content {
  background-color: #fff;
  margin: 10% auto;
  padding: 2rem;
  border-radius: var(--radius);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
  width: 90%;
  max-width: 600px;
  position: relative;
}

.modal-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  color: #aaa;
  font-size: 1.5rem;
  font-weight: bold;
  cursor: pointer;
}

.modal-close:hover {
  color: #555;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #eee;
  color: #333;
}

.notice-badge {
  display: inline-block;
  background-color: var(--blue);
  color: white;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  margin-right: 0.5rem;
}

.post-title-link {
  text-decoration: none;
  color: #333;
  transition: color 0.2s;
}

.post-title-link:hover {
  color: var(--blue);
}

/* 반응형 스타일 */
@media (max-width: 768px) {
  .form-row {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .form-group,
  .form-group-small {
    width: 100%;
  }
  
  .board-table,
  .post-table {
    display: block;
    overflow-x: auto;
  }
}
</style>

<main class="admin-community container">
  <h2 class="admin-title"><br>커뮤니티 관리</h2>
  
  <?php if (isset($success_message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
  <?php endif; ?>
  
  <?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
  <?php endif; ?>
  
  <!-- 게시판 관리 섹션 -->
  <section class="admin-section">
    <h3 class="admin-section-title">게시판 관리</h3>
    
    <!-- 게시판 추가/수정 폼 -->
    <form method="post" class="board-form" id="boardForm">
      <input type="hidden" name="board_id" id="boardId" value="">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="boardName">게시판 이름</label>
          <input type="text" id="boardName" name="name" class="form-input" placeholder="게시판 이름" required>
        </div>
        <div class="form-group-small">
          <label class="form-label" for="sortOrder">정렬 순서</label>
          <input type="number" id="sortOrder" name="sort_order" class="form-input" value="0" min="0">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="boardDescription">게시판 설명</label>
          <input type="text" id="boardDescription" name="description" class="form-input" placeholder="게시판 설명 (선택사항)">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"></div>
        <button type="submit" name="add_board" id="boardSubmitBtn" class="form-submit">게시판 추가</button>
      </div>
    </form>
    
    <!-- 게시판 목록 테이블 -->
    <table class="board-table">
      <thead>
        <tr>
          <th style="width: 10%;">ID</th>
          <th style="width: 20%;">이름</th>
          <th style="width: 40%;">설명</th>
          <th style="width: 10%;">정렬 순서</th>
          <th style="width: 20%;">관리</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($boards) > 0): ?>
          <?php foreach ($boards as $board): ?>
            <tr>
              <td><?= $board['id'] ?></td>
              <td><?= htmlspecialchars($board['name']) ?></td>
              <td><?= htmlspecialchars($board['description'] ?? '') ?></td>
              <td><?= $board['sort_order'] ?></td>
              <td>
                <div class="board-actions">
                  <a href="community_board.php?id=<?= $board['id'] ?>" class="view-link" target="_blank">보기</a>
                  <a href="#" class="edit-link" onclick="editBoard(<?= $board['id'] ?>, '<?= htmlspecialchars(addslashes($board['name'])) ?>', '<?= htmlspecialchars(addslashes($board['description'] ?? '')) ?>', <?= $board['sort_order'] ?>); return false;">수정</a>
                  <a href="admin_community.php?delete_board=<?= $board['id'] ?>" class="delete-link" onclick="return confirm('정말 삭제하시겠습니까? 게시판에 게시글이 없어야 삭제할 수 있습니다.')">삭제</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" style="text-align: center;">등록된 게시판이 없습니다.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>
  
  <!-- 최근 게시글 섹션 -->
  <section class="admin-section">
    <h3 class="admin-section-title">최근 게시글</h3>
    <table class="post-table">
      <thead>
        <tr>
          <th style="width: 10%;">ID</th>
          <th style="width: 15%;">게시판</th>
          <th style="width: 35%;">제목</th>
          <th style="width: 15%;">작성자</th>
          <th style="width: 15%;">작성일</th>
          <th style="width: 10%;">관리</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($recent_posts) > 0): ?>
          <?php foreach ($recent_posts as $post): ?>
            <tr>
              <td><?= $post['id'] ?></td>
              <td><?= htmlspecialchars($post['board_name']) ?></td>
              <td>
                <?php if ($post['is_notice']): ?>
                  <span class="notice-badge">공지</span>
                <?php endif; ?>
                <a href="community_post.php?id=<?= $post['id'] ?>" class="post-title-link" target="_blank"><?= htmlspecialchars($post['title']) ?></a>
              </td>
              <td><?= htmlspecialchars($post['user_name']) ?></td>
              <td><?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></td>
              <td>
                <div class="post-actions">
                  <a href="community_post.php?id=<?= $post['id'] ?>" class="view-link" target="_blank">보기</a>
                  <a href="community_process.php?action=delete&id=<?= $post['id'] ?>" class="delete-link" onclick="return confirm('정말 삭제하시겠습니까?')">삭제</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align: center;">등록된 게시글이 없습니다.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
    
    <div style="text-align: center; margin-top: 1rem;">
      <a href="community.php" target="_blank" class="form-submit" style="text-decoration: none;">커뮤니티 메인으로 이동</a>
    </div>
  </section>
</main>

<!-- 모달 요소 추가 -->
<div id="boardEditModal" class="modal">
  <div class="modal-content">
    <span class="modal-close">&times;</span>
    <h3 class="modal-title">게시판 수정</h3>
    
    <form method="post" class="board-form">
      <input type="hidden" name="board_id" id="modalBoardId" value="">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="modalBoardName">게시판 이름</label>
          <input type="text" id="modalBoardName" name="name" class="form-input" placeholder="게시판 이름" required>
        </div>
        <div class="form-group-small">
          <label class="form-label" for="modalSortOrder">정렬 순서</label>
          <input type="number" id="modalSortOrder" name="sort_order" class="form-input" value="0" min="0">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="modalBoardDescription">게시판 설명</label>
          <input type="text" id="modalBoardDescription" name="description" class="form-input" placeholder="게시판 설명 (선택사항)">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"></div>
        <button type="submit" name="edit_board" class="form-submit">수정하기</button>
      </div>
    </form>
  </div>
</div>

<script>
// 게시판 수정 함수
function editBoard(id, name, description, sortOrder) {
  // 모달 폼에 값 설정
  document.getElementById('modalBoardId').value = id;
  document.getElementById('modalBoardName').value = name;
  document.getElementById('modalBoardDescription').value = description;
  document.getElementById('modalSortOrder').value = sortOrder;
  
  // 모달 표시
  const modal = document.getElementById('boardEditModal');
  modal.style.display = "block";
}

// 모달 닫기 이벤트
const closeBtn = document.querySelector('.modal-close');
const modal = document.getElementById('boardEditModal');

closeBtn.addEventListener('click', function() {
  modal.style.display = "none";
});

// 모달 외부 클릭 시 닫기
window.addEventListener('click', function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
});
</script>

<?php include 'footer.php'; ?>