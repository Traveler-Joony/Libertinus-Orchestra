<?php
session_start();
require_once 'config.php';
require_once 'header.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
  echo "<main class='login-wrap'><div class='login-box'><p>⚠️ 관리자 권한이 없습니다.</p></div></main>";
  require_once 'footer.php';
  exit;
}

$user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$app_count = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
?>

<style>
.admin-container {
  max-width: var(--max-width);
  margin: 0 auto;
  padding: 100px 1.25rem 3rem;
}

.admin-title {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 2rem;
}

.admin-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.admin-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

.admin-card {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 14px;
  padding: 2rem;
  box-shadow: 0 4px 16px rgba(0,0,0,0.05);
  text-align: center;
  transition: all 0.2s;
  text-decoration: none;
  color: var(--gray-800);
  font-weight: 600;
  font-size: 1.05rem;
}
.admin-card:hover {
  background: var(--blue);
  color: #fff;
  border-color: var(--blue);
}
.admin-card i {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
  display: block;
}

.dashboard {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.stat-box {
  background: #f9f9f9;
  border: 1px solid #ccc;
  border-radius: 12px;
  padding: 1.5rem;
  text-align: center;
}
.stat-box h3 {
  font-size: 1.1rem;
  color: var(--gray-600);
  margin-bottom: .5rem;
}
.stat-box .count {
  font-size: 2rem;
  font-weight: bold;
  color: var(--blue);
}
</style>

<main class="admin-container">
  <h2 class="admin-title"><br>🔧 관리자 페이지</h2>

  <div class="admin-layout">
    <!-- 좌측: 메뉴 -->
    <div class="admin-grid">
      <a href="admin_members.php" class="admin-card">
        <i class="fa-solid fa-users"></i>
        단원 정보 관리
      </a>

      <a href="admin_applications.php" class="admin-card">
        <i class="fa-solid fa-address-card"></i>
        입단 지원자 관리
      </a>

      <a href="admin_news.php" class="admin-card">
        <i class="fa-solid fa-newspaper"></i>
        소식 작성 및 편집
      </a>

      <a href="admin_performances.php" class="admin-card">
        <i class="fa-solid fa-music"></i>
        공연 작성 및 편집
      </a>

      <a href="logout.php" class="admin-card">
        <i class="fa-solid fa-right-from-bracket"></i>
        로그아웃
      </a>
    </div>

    <!-- 우측: 대시보드 -->
    <div class="dashboard">
      <div class="stat-box">
        <h3>전체 회원 수</h3>
        <div class="count"><?= $user_count ?></div>
      </div>
      <div class="stat-box">
        <h3>입단 지원자 수</h3>
        <div class="count"><?= $app_count ?></div>
      </div>
    </div>
  </div>
</main>

<?php require_once 'footer.php'; ?>
