<?php
session_start();
require_once 'config.php';
require_once 'header.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
  echo "<main class='login-wrap'><div class='login-box'><p>⚠️ 관리자 권한이 없습니다.</p></div></main>";
  require_once 'footer.php';
  exit;
}

// 삭제 처리
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $pdo->prepare("DELETE FROM applications WHERE id=?")->execute([$id]);
  echo "<script>alert('삭제되었습니다.'); location.href='admin_applications.php';</script>";
  exit;
}

// 검색 / 필터
$search = trim($_GET['search'] ?? '');
$instrument = $_GET['instrument'] ?? '';
$params = [];
$where = [];

if ($search) {
  $where[] = "(name LIKE ? OR student_id LIKE ? OR dept LIKE ? OR instrument LIKE ?)";
  array_push($params, "%$search%", "%$search%", "%$search%", "%$search%");
}
if ($instrument) {
  $where[] = "instrument = ?";
  $params[] = $instrument;
}

$sql = "SELECT * FROM applications";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

// 악기 목록 추출
$instruments = $pdo->query("SELECT DISTINCT instrument FROM applications ORDER BY instrument")->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
.filter-bar input[type="text"] {
  flex: 1 1 300px;
  min-width: 240px;
  max-width: 350px;
}

    
.container {
  max-width: var(--max-width);
  margin: 0 auto;
  padding: 2rem 1.25rem;
}

.grid--3 {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}
.card {
  background: #fff;
  border: 1px solid #ccc;
  border-radius: 14px;
  padding: 1.5rem;
  box-shadow: 0 4px 16px rgba(0,0,0,0.05);
  position: relative;
}
.card .row { margin-bottom: .5rem; font-size: 0.95rem; }
.label { font-weight: bold; color: #555; width: 100px; display: inline-block; }

.filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 2rem;
}
.filter-bar input,
.filter-bar select {
  padding: .65rem;
  font-size: 1rem;
  border: 1px solid #ccc;
  border-radius: 6px;
}
.filter-bar button {
  padding: .65rem 1.25rem;
  font-weight: 600;
  background: var(--blue);
  color: #fff;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
.btn-delete {
  position: absolute;
  top: 1rem;
  right: 1rem;
  font-size: .85rem;
  background: #dc3545;
  color: #fff;
  padding: .4rem .8rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
</style>

<main class="container" style="padding-top: 100px;">
  <h2 style="margin-bottom:2rem;"><Br><Br>📥 입단 지원 목록</h2>

  <form class="filter-bar" method="get">
    <input type="text" name="search" placeholder="이름, 학번, 학과, 악기 검색" value="<?= htmlspecialchars($search) ?>">
    <select name="instrument">
      <option value="">전체 악기</option>
      <?php foreach ($instruments as $opt): ?>
        <option value="<?= htmlspecialchars($opt) ?>" <?= $instrument === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">검색</button>
  </form>

  <div class="grid grid--3">
    <?php foreach ($applications as $a): ?>
      <div class="card">
        <button class="btn-delete" onclick="return confirmDelete(<?= $a['id'] ?>)">삭제</button>

        <div class="row"><span class="label">이름</span> <?= htmlspecialchars($a['name']) ?></div>
        <div class="row"><span class="label">지원일</span> <?= date('Y-m-d H:i', strtotime($a['created_at'])) ?></div>
        <div class="row"><span class="label">생년월일</span> <?= htmlspecialchars($a['birth']) ?></div>
        <div class="row"><span class="label">학번</span> <?= htmlspecialchars($a['student_id']) ?></div>
        <div class="row"><span class="label">학과</span> <?= htmlspecialchars($a['dept']) ?></div>
        <div class="row"><span class="label">악기</span> <?= htmlspecialchars($a['instrument']) ?></div>
        <div class="row"><span class="label">전화</span> <?= htmlspecialchars($a['mobile']) ?></div>
        <div class="row"><span class="label">이메일</span> <?= htmlspecialchars($a['email']) ?></div>
        <?php if ($a['message']): ?>
          <div class="row"><span class="label">하고 싶은 말</span><br><?= nl2br(htmlspecialchars($a['message'])) ?></div>
        <?php endif; ?>
        
      </div>
    <?php endforeach; ?>
  </div>
</main>

<script>
function confirmDelete(id) {
  if (confirm("정말 이 지원서를 삭제하시겠습니까?")) {
    location.href = "admin_applications.php?delete=" + id;
  }
  return false;
}
</script>

<?php require_once 'footer.php'; ?>
