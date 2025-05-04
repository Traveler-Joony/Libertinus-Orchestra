<?php
session_start();
require_once 'config.php';

// ——— 이미지 삭제 → 기본값으로 변경 처리 (header.php 이전) ———
if (isset($_GET['delete_img']) && is_numeric($_GET['delete_img'])) {
  $id = intval($_GET['delete_img']);

  // 1) 기존 프로필 파일명 조회
  $img_query = $conn->query("SELECT profile_img FROM users WHERE id=$id");
  if ($img_query && $img_query->num_rows) {
      $old = $img_query->fetch_assoc()['profile_img'];

      // 2) DB에 기본 프로필로 덮어쓰기 (파일 삭제 없음)
      $default = 'profile_6800f3c5a647f.png';
      $conn->query("UPDATE users SET profile_img = '{$default}' WHERE id = $id");
  }

  // 파라미터 제거 후 리다이렉트
  $qs = $_GET; unset($qs['delete_img']);
  $url = 'admin_members.php' . (empty($qs) ? '' : '?' . http_build_query($qs));
  header("Location: {$url}");
  exit;
}


require_once 'header.php';

// 디버깅 함수 추가
function debug_log($message) {
  // 파일에 로그를 기록
  error_log($message, 3, __DIR__ . "/debug.log");
}

// 관리자 권한 체크
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
  echo "<main class='login-wrap'><div class='login-box'><p>⚠️ 관리자 권한이 없습니다.</p></div></main>";
  require_once 'footer.php';
  exit;
}

// 이미지 관련 설정 중앙화
$profile_img_dir = "img/profile/"; // 상대 경로 (화면 표시용)
$profile_img_path = __DIR__ . "/" . $profile_img_dir; // 절대 경로 (파일 시스템 작업용)

// 이미지, 디렉토리 존재 확인 및 생성
if (!file_exists($profile_img_path)) {
  if (!mkdir($profile_img_path, 0755, true)) {
    debug_log("프로필 이미지 디렉토리 생성 실패: {$profile_img_path}");
  } else {
    debug_log("프로필 이미지 디렉토리 생성됨: {$profile_img_path}");
  }
}

function calculateGeneration($joinYearSem) {
  if (empty($joinYearSem)) return "-";
  $parts = explode('-', $joinYearSem);
  $year = isset($parts[0]) ? intval($parts[0]) : 0;
  return ($year >= 2014) ? ($year - 2014 + 33) . "기" : "-";
}

// --- 기수 목록 조회 쿼리 수정 ---
$generationQuery = $conn->query("
  SELECT DISTINCT 
    CONCAT( (SUBSTRING_INDEX(join_year_sem, '-', 1) - 2014 + 33 ), '기') AS generation 
  FROM users 
  WHERE join_year_sem LIKE '____-_' /* YYYY-S 형식 강제 */
  ORDER BY generation+0 ASC
");
$generations = $generationQuery->fetch_all(MYSQLI_ASSOC);

// --- 악기 정렬 로직 강화 ---
$instrumentPriority = [
  '바이올린','비올라','첼로','콘트라베이스',
  '피콜로','플룻','클라리넷','오보에','바순','색소폰',
  '호른','트럼펫','트롬본','튜바',
  '팀파니','스네어드럼','베이스드럼','심벌','마림바','비브라폰',
  '피아노','비연주단원','기타'
];
$instrumentQuery = $conn->query("SELECT DISTINCT instrument FROM users");
$instruments = array_column($instrumentQuery->fetch_all(MYSQLI_ASSOC), 'instrument');
$sortedInstruments = array_intersect($instrumentPriority, $instruments);

// --- 필터 처리 로직 통합 및 수정 ---
$conditions = [];
$filter_status = $_GET['filter_status'] ?? '';
$search = trim($_GET['search'] ?? '');
$filter_generation = $_GET['filter_generation'] ?? '';
$filter_instrument = $_GET['filter_instrument'] ?? '';

// 상태 필터
if (in_array($filter_status, ['0', '1'])) {
  $conditions[] = "graduate = " . intval($filter_status);
}

// 검색어 필터
if ($search !== '') {
  $searchTerm = $conn->real_escape_string($search);
  $conditions[] = "(name LIKE '%$searchTerm%' OR student_id LIKE '%$searchTerm%' OR instrument LIKE '%$searchTerm%')";
}

// 기수 필터 (핵심 수정)
if ($filter_generation !== '') {
  $genNumber = intval(str_replace('기', '', $filter_generation));
  $targetYear = $genNumber - 33 + 2014;
  $conditions[] = "SUBSTRING_INDEX(join_year_sem, '-', 1) = '" . $conn->real_escape_string($targetYear) . "'";
}

// 악기 필터 (대소문자 처리)
if ($filter_instrument !== '') {
  $instrument = $conn->real_escape_string(strtolower($filter_instrument));
  $conditions[] = "LOWER(instrument) = '$instrument'";
}

// 최종 쿼리 조합
$where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
$result = $conn->query("SELECT * FROM users $where ORDER BY name ASC");
$total_results = $result->num_rows;





// 수정처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
  $id = intval($_POST['update_id']);
  $name = $_POST['name'];
  $birth = $_POST['birth'];
  $email = $_POST['email'];
  $student_id = $_POST['student_id'];
  $dept = $_POST['dept'];
  $mobile = $_POST['mobile'];
  $instrument = $_POST['instrument'];
  $join_year_sem = $_POST['join_year_sem'];
  $graduate = intval($_POST['graduate']);
  $position = $_POST['position'];
  $profile_img = $_POST['existing_img'] ?? '';
  $is_leader = isset($_POST['is_leader']) ? 1 : 0;

  // POST 데이터 처리 부분에 추가
  $is_admin = isset($_POST['is_admin']) ? 1 : 0;  // 관리자 체크박스 값 처리


  // 이미지 업로드 처리
  if (!empty($_FILES['profile_img']['name']) && is_uploaded_file($_FILES['profile_img']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION));
    
    // 확장자 검증
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
      $filename = uniqid("profile_") . "." . $ext;
      $target = $profile_img_path . $filename;
      
      // 디렉토리 존재 확인 및 생성
      if (!file_exists(dirname($target))) {
        mkdir(dirname($target), 0755, true);
        debug_log("이미지 디렉토리 생성됨: " . dirname($target));
      }
      
      // 디렉토리 쓰기 권한 확인
      if (!is_writable(dirname($target))) {
        debug_log("디렉토리 쓰기 권한 없음: " . dirname($target));
        @chmod(dirname($target), 0755);
      }
      
      // 파일 업로드 시도
      if (move_uploaded_file($_FILES['profile_img']['tmp_name'], $target)) {
        debug_log("파일 업로드 성공: " . $target);
        
        // 새 이미지 파일명으로 업데이트 (기존 파일 삭제하지 않음)
        $profile_img = $filename;
      } else {
        $error_code = $_FILES['profile_img']['error'];
        $error_msg = match($error_code) {
          UPLOAD_ERR_INI_SIZE => "파일이 php.ini에 설정된 최대 크기를 초과합니다.",
          UPLOAD_ERR_FORM_SIZE => "파일이 HTML 폼에 설정된 최대 크기를 초과합니다.",
          UPLOAD_ERR_PARTIAL => "파일이 일부만 업로드되었습니다.",
          UPLOAD_ERR_NO_FILE => "파일이 업로드되지 않았습니다.",
          UPLOAD_ERR_NO_TMP_DIR => "임시 폴더가 없습니다.",
          UPLOAD_ERR_CANT_WRITE => "디스크에 파일을 쓸 수 없습니다.",
          UPLOAD_ERR_EXTENSION => "PHP 확장에 의해 업로드가 중지되었습니다.",
          default => "알 수 없는 오류가 발생했습니다."
        };
        
        debug_log("파일 업로드 실패: " . $error_msg . " (코드: " . $error_code . ")");
        debug_log("대상 경로: " . $target);
        debug_log("디렉토리 쓰기 권한: " . (is_writable(dirname($target)) ? "있음" : "없음"));
        
        echo "<script>alert('이미지 업로드 오류: {$error_msg}');</script>";
      }
    } else {
      echo "<script>alert('지원되지 않는 이미지 형식입니다. JPG, PNG, WEBP만 가능합니다.');</script>";
    }
  }

  // DB 업데이트
  $stmt = $conn->prepare("UPDATE users SET name=?, birth=?, email=?, student_id=?, dept=?, mobile=?, instrument=?, join_year_sem=?, graduate=?, position=?, profile_img=?, is_leader=?, is_admin=? WHERE id=?");

  if ($stmt) {
    
    $stmt->bind_param("ssssssssissiii", $name, $birth, $email, $student_id, $dept, $mobile, $instrument, $join_year_sem, $graduate, $position, $profile_img, $is_leader, $is_admin, $id);
    
    if ($stmt->execute()) {
      debug_log("사용자 ID {$id} 정보 업데이트 성공");
      
      // 단순히 admin_members.php로 리다이렉트
      echo "<script>
              alert('수정되었습니다.');
              window.location.href = 'admin_members.php?t=" . time() . "';
            </script>";
      exit;
    } else {
      debug_log("사용자 정보 업데이트 실패: " . $stmt->error);
      echo "<script>alert('사용자 정보 업데이트 중 오류가 발생했습니다.');</script>";
    }
  } else {
    debug_log("SQL 준비 실패: " . $conn->error);
    echo "<script>alert('데이터베이스 오류가 발생했습니다.');</script>";
  }
}

// 삭제 처리
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
  $delete_id = intval($_GET['delete_user']);
  
  // 사용자 데이터 삭제 (이미지 파일은 삭제하지 않음)
  if ($conn->query("DELETE FROM users WHERE id = $delete_id")) {
    debug_log("사용자 ID {$delete_id} 삭제 성공");
    
    // 현재 URL의 쿼리 스트링에서 delete_user 파라미터 제거 후 리다이렉트
    $redirectParams = $_GET;
    unset($redirectParams['delete_user']);
    
    $redirectUrl = 'admin_members.php';
    if (!empty($redirectParams)) {
      $redirectUrl .= '?' . http_build_query($redirectParams);
    }
    
    echo "<script>alert('삭제되었습니다.'); location.href='" . $redirectUrl . "';</script>";
    exit;
  } else {
    debug_log("사용자 삭제 실패: " . $conn->error);
    echo "<script>alert('사용자 삭제 중 오류가 발생했습니다.'); location.href='admin_members.php';</script>";
    exit;
  }
}

// 삭제 처리
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
  $delete_id = intval($_GET['delete_user']);
  
  // 사용자 데이터 삭제 (이미지 파일은 삭제하지 않음)
  if ($conn->query("DELETE FROM users WHERE id = $delete_id")) {
    debug_log("사용자 ID {$delete_id} 삭제 성공");
    echo "<script>alert('삭제되었습니다.'); location.href='admin_members.php';</script>";
    exit;
  } else {
    debug_log("사용자 삭제 실패: " . $conn->error);
    echo "<script>alert('사용자 삭제 중 오류가 발생했습니다.'); location.href='admin_members.php';</script>";
    exit;
  }
}
?>

<style>
.member-form .checkbox-row {
  grid-column: span 2;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.95rem;
  margin: 0.5rem 0;
  padding: 0.5rem;
  background-color: #f8f9fa;
  border-radius: 6px;
  border: 1px solid #e9ecef;
}

.member-form .checkbox-row input[type="checkbox"] {
  transform: scale(1.2);
  margin: 0;
  cursor: pointer;
  accent-color: var(--blue);
}

.admin-container {
  max-width: var(--max-width);
  margin: 0 auto;
  padding: 120px 1.25rem 2rem;
}

.members-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.75rem;
  width: 100%;
}

.member-card {
  position: relative;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 14px;
  padding: 1.75rem;
  box-shadow: 0 6px 18px rgba(0,0,0,0.08);
  transition: all 0.2s ease;
}

.member-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.member-card img {
  width: 80px; 
  height: 80px;
  border-radius: 50%; 
  object-fit: cover; 
  margin-bottom: 1rem;
  border: 3px solid #f0f0f0;
}

.label { 
  font-weight: 600; 
  color: #444; 
  width: 70px;  /* 100px에서 70px로 변경 */
  display: inline-block;
}

.content-value {
  display: inline-block;
  word-break: break-all;
  max-width: calc(100% - 75px);
  vertical-align: top;
}

.row { 
  margin-bottom: .7rem; 
  font-size: 0.95rem;
  line-height: 1.4;
}

.action-buttons {
  display: flex;
  gap: 0.75rem;
  position: absolute;
  top: 1rem;
  right: 1rem;
}

.btn-edit {
  font-size: 0.9rem; 
  background: var(--blue); 
  color: #fff;
  border: none; 
  border-radius: 6px; 
  padding: 0.4rem 0.8rem; 
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-edit:hover {
  background-color: #0056b3;
}

.member-form { 
  display: none; 
  margin-top: 1.5rem;
  border-top: 1px solid #eee;
  padding-top: 1.5rem;
}

.member-form.active {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.9rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.form-group label {
  font-size: 0.85rem;
  color: #555;
  font-weight: 500;
}

.member-form input[type="text"],
.member-form input[type="email"],
.member-form input[type="date"],
.member-form select {
  font-size: 0.95rem; 
  border: 1px solid #ccc;
  border-radius: 6px; 
  padding: .5rem; 
  width: 100%;
  transition: border-color 0.2s;
}

.member-form input:focus,
.member-form select:focus {
  border-color: var(--blue);
  outline: none;
  box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
}

.member-form input[type="file"] {
  font-size: 0.9rem;
  padding: 0.3rem 0;
}

.member-form button {
  grid-column: span 2;
  padding: 0.7rem;
  background: var(--blue);
  color: #fff;
  font-weight: 600;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s;
  margin-top: 0.5rem;
}

.member-form button:hover {
  background-color: #0056b3;
}

.filter-bar {
  display: flex;
  flex-wrap: nowrap;
  gap: 1rem;
  margin-bottom: 2.5rem;
  width: 100%;
  align-items: center;
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 10px;
  /*box-shadow: 0 2px 8px rgba(0,0,0,0.05); */
}

.filter-bar input.form-input,
.filter-bar select.form-input {
  flex: 1 1 auto;
  max-width: 240px;
  border: 1px solid #ddd;
  border-radius: 6px;
  padding: 0.7rem;
  font-size: 0.95rem;
}

.filter-bar .btn-join {
  flex: 0 0 120px;
  padding: 1rem 1.2rem;              /* input과 동일한 상하 패딩 */
  font-size: 1rem;
  font-weight: 700;
  line-height: 0.8;
  background: var(--blue);
  color: #fff;
  border: none;
  border-radius: var(--radius);
  cursor: pointer;
  box-sizing: border-box;
  align-self: center;
  height: auto;
  margin-top: -1rem !important;         /* 🔥 기존 1.7rem 제거 */
}

.filter-bar .btn-join:hover {
  background-color: #0056b3;
}

.btn-delete {
  background: #dc3545 !important;
  display: block;
  /*width: fit-content;*/
  margin-top: 1rem;
}

.btn-delete:hover {
  background: #c82333 !important;
}

.img-delete-btn {
  margin-bottom: 0.5rem;
}

.img-delete-btn a {
  color: #dc3545;
  font-size: 0.85rem;
  text-decoration: none;
  display: inline-block;
  padding: 0.3rem 0.6rem;
  background: #f8d7da;
  border-radius: 4px;
}

.img-delete-btn a:hover {
  background: #f5c6cb;
  text-decoration: underline;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #333;
}

/* 반응형 디자인 */
@media (max-width: 768px) {
  .members-grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  }
  
  .filter-bar {
    flex-wrap: wrap;
  }
}

@media (max-width: 480px) {
  .members-grid {
    grid-template-columns: 1fr;
  }
  
  .filter-bar {
    flex-direction: column;
  }
  
  .filter-bar input.form-input,
  .filter-bar select.form-input,
  .filter-bar .btn-join {
    max-width: 100%;
    width: 100%;
  }
  
  .member-form.active {
    grid-template-columns: 1fr;
  }
  
  .member-form button,
  .member-form .checkbox-row {
    grid-column: span 1;
  }
}

/* 결과 개수 스타일 추가 */
.result-count {
  margin: -1rem 0 1.5rem;
  padding: 0.8rem 1.2rem;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
  font-size: 0.95rem;
  color: #444;
  font-weight: 500;
}

.result-count::before {
  content: "🔍 ";
}
</style>

<main class="admin-container">
  <div class="section-header">
    <h2 class="page-title"><br>👥 단원 관리</h2>
  </div>

  <form class="filter-bar" method="get">
    <input class="form-input" type="text" name="search" placeholder="이름, 학번, 파트 검색" value="<?= htmlspecialchars($search) ?>">

    <!-- 기수 필터 추가 -->
    <select class="form-input" name="filter_generation">
      <option value="">전체 기수</option>
      <?php foreach($generations as $gen): ?>
        <option value="<?= $gen['generation'] ?>" <?= $filter_generation == $gen['generation'] ? 'selected' : '' ?>>
          <?= $gen['generation'] ?>
        </option>
      <?php endforeach; ?>
    </select>

    <!-- 악기 필터 추가 -->
    <select class="form-input" name="filter_instrument">
      <option value="">전체 악기</option>
      <?php foreach($sortedInstruments as $inst): ?>
        <option value="<?= $inst ?>" <?= $filter_instrument == $inst ? 'selected' : '' ?>>
          <?= $inst ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select class="form-input" name="filter_status">
      <option value="">활동상태</option>
      <option value="0" <?= $filter_status === '0' ? 'selected' : '' ?>>활동중</option>
      <option value="1" <?= $filter_status === '1' ? 'selected' : '' ?>>졸업/탈퇴</option>
    </select>
    <button class="btn-join" type="submit">검색</button>
  </form>

  <div class="result-count">
  <?php if($search || $filter_status !== '' || $filter_generation !== '' || $filter_instrument !== ''): ?>
    검색 결과 (<?= $total_results ?>개)
  <?php else: ?>
    전체 단원 (<?= $total_results ?>명)
  <?php endif; ?>
  </div>

  <div class="members-grid">
    <?php while ($row = $result->fetch_assoc()): ?>
      <div class="member-card" id="card-<?= $row['id'] ?>">
        <div class="member-view">
          <?php if ($row['profile_img']): ?>
            <img src="../img/profile/<?= htmlspecialchars($row['profile_img']) ?>" alt="프로필">
            <div class="img-delete-btn" style="display: none;">
              <a href="?delete_img=<?= $row['id'] ?>&<?= http_build_query($_GET) ?>">이미지 삭제</a>
            </div>
          <?php else: ?>
            <div style="width:80px;height:80px;border-radius:50%;background:#eee;margin-bottom:1rem;display:flex;align-items:center;justify-content:center;">
              <span style="font-size:1.5rem;color:#aaa;">👤</span>
            </div>
          <?php endif; ?>

          <div class="row"><span class="label">이름</span> <?= htmlspecialchars($row['name']) ?></div>
          <div class="row"><span class="label">기수</span> <?= calculateGeneration($row['join_year_sem']) ?></div>
          <div class="row"><span class="label">파트</span> <?= htmlspecialchars($row['instrument']) ?></div>
          <div class="row"><span class="label">학번</span> <?= htmlspecialchars($row['student_id']) ?></div>
          <div class="row"><span class="label">학과</span> <?= htmlspecialchars($row['dept']) ?></div>
          <div class="row"><span class="label">입부</span> <?= htmlspecialchars($row['join_year_sem']) ?></div>
          <div class="row"><span class="label">전화</span> <?= htmlspecialchars($row['mobile']) ?></div>
          <div class="row"><span class="label">이메일</span> <?= htmlspecialchars($row['email']) ?></div>
          <?php if($row['position']): ?>
          <div class="row"><span class="label">직책</span> <?= htmlspecialchars($row['position']) ?></div>
          <?php endif; ?>
          <div class="row"><span class="label">상태</span> <?= $row['graduate'] ? '졸업/탈퇴' : '활동중' ?></div>
          <?php if($row['is_leader']): ?>
          <div class="row" style="color:var(--blue);font-weight:600;"><span class="label">파트장</span> ✓</div>
          <?php endif; ?>
        </div>

        <div class="action-buttons">
          <button class="btn-edit" onclick="toggleEdit(<?= $row['id'] ?>)">수정</button>
        </div>

        <form method="post" enctype="multipart/form-data" class="member-form" id="form-<?= $row['id'] ?>"action="admin_members.php<?= !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : '' ?>">
          <input type="hidden" name="update_id" value="<?= $row['id'] ?>">
          <input type="hidden" name="existing_img" value="<?= $row['profile_img'] ?>">

          <div class="form-group" style="grid-column: span 2;">
            <label for="profile_img_<?= $row['id'] ?>">프로필 이미지</label>
            <input type="file" id="profile_img_<?= $row['id'] ?>" name="profile_img" accept="image/*">
          </div>
          
          <div class="form-group">
            <label for="name_<?= $row['id'] ?>">이름</label>
            <input type="text" id="name_<?= $row['id'] ?>" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
          </div>
          
          <div class="form-group">
            <label for="birth_<?= $row['id'] ?>">생년월일</label>
            <input type="date" id="birth_<?= $row['id'] ?>" name="birth" value="<?= $row['birth'] ?>" required>
          </div>
          
          <div class="form-group">
            <label for="email_<?= $row['id'] ?>">이메일</label>
            <input type="email" id="email_<?= $row['id'] ?>" name="email" value="<?= htmlspecialchars($row['email']) ?>" required>
          </div>
          
          <div class="form-group">
            <label for="student_id_<?= $row['id'] ?>">학번</label>
            <input type="text" id="student_id_<?= $row['id'] ?>" name="student_id" value="<?= htmlspecialchars($row['student_id']) ?>" required>
          </div>
          
          <div class="form-group">
            <label for="dept_<?= $row['id'] ?>">학과</label>
            <input type="text" id="dept_<?= $row['id'] ?>" name="dept" value="<?= htmlspecialchars($row['dept']) ?>" required>
          </div>
          
          <div class="form-group">
            <label for="mobile_<?= $row['id'] ?>">전화번호</label>
            <input type="text" id="mobile_<?= $row['id'] ?>" name="mobile" value="<?= htmlspecialchars($row['mobile']) ?>" required>
          </div>
          
          <div class="form-group">
            <label for="instrument_<?= $row['id'] ?>">파트</label>
            <input type="text" id="instrument_<?= $row['id'] ?>" name="instrument" value="<?= htmlspecialchars($row['instrument']) ?>" required>
          </div>
          
          <div class="form-group">
            <label for="join_year_sem_<?= $row['id'] ?>">입부 년도/학기</label>
            <input type="text" id="join_year_sem_<?= $row['id'] ?>" name="join_year_sem" value="<?= htmlspecialchars($row['join_year_sem']) ?>" required>
          </div>
          
          <div class="form-group">
            <label for="graduate_<?= $row['id'] ?>">상태</label>
            <select id="graduate_<?= $row['id'] ?>" name="graduate">
              <option value="0" <?= $row['graduate'] == 0 ? 'selected' : '' ?>>활동중</option>
              <option value="1" <?= $row['graduate'] == 1 ? 'selected' : '' ?>>졸업/탈퇴</option>
            </select>
          </div>

          <div class="form-group">
            <label for="position_<?= $row['id'] ?>">직책</label>
            <input type="text" id="position_<?= $row['id'] ?>" name="position" value="<?= htmlspecialchars($row['position']) ?>" placeholder="직책">
          </div>

          <label class="checkbox-row">
            <input type="checkbox" name="is_leader" value="1" <?= $row['is_leader'] ? 'checked' : '' ?>>
            <span><strong>파트장 여부</strong></span>
          </label>

          <label class="checkbox-row">
            <input type="checkbox" name="is_admin" value="1" <?= $row['is_admin'] ? 'checked' : '' ?>>
            <span><strong>관리자 계정 부여</strong></span>
          </label>
          
          <button type="submit">저장</button>
          <button type="button" class="btn-delete" onclick="return confirmDelete(<?= $row['id'] ?>)">계정 삭제</button>
        </form>
      </div>
    <?php endwhile; ?>
  </div>
</main>

<?php if (isset($_GET['updated'])): ?>
  <script>alert('수정되었습니다.');</script>
<?php endif; ?>

<script>
function toggleEdit(id) {
  document.querySelectorAll('.member-form').forEach(f => f.classList.remove('active'));
  
  const card = document.getElementById('card-' + id);
  const form = document.getElementById('form-' + id);
  
  // 모든 이미지 삭제 버튼 숨기기
  document.querySelectorAll('.img-delete-btn').forEach(btn => {
    btn.style.display = 'none';
  });
  
  if(form.classList.contains('active')) {
    form.classList.remove('active');
  } else {
    form.classList.add('active');
    // 현재 카드의 이미지 삭제 버튼만 표시
    const deleteBtn = card.querySelector('.img-delete-btn');
    if(deleteBtn) deleteBtn.style.display = 'block';
  }
}

function confirmDelete(id) {
  if (confirm("정말 계정을 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.")) {
    // 현재 URL에서 쿼리 스트링 가져오기
    const urlParams = new URLSearchParams(window.location.search);
    
    // delete_user 파라미터 추가
    urlParams.set('delete_user', id);
    
    // 리다이렉트
    location.href = "admin_members.php?" + urlParams.toString();
    return false;
  }
  return false;
}
</script>