<?php
session_start();
require_once 'config.php';

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
  echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
  exit;
}

// 유저 정보 가져오기
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
  echo "<script>alert('사용자 정보를 찾을 수 없습니다.'); location.href='index.php';</script>";
  exit;
}

// 이미지 관련 설정
$profile_img_dir = "/img/profile/"; // 상대 경로 (화면 표시용)
$profile_img_path = __DIR__ . "/img/profile/"; // 절대 경로 (파일 시스템 작업용)

// 이미지 디렉토리 존재 확인 및 생성
if (!file_exists($profile_img_path)) {
  mkdir($profile_img_path, 0755, true);
}

function calculateGeneration($joinYearSem) {
  if (empty($joinYearSem)) return "-";
  $parts = explode('-', $joinYearSem);
  $year = isset($parts[0]) ? intval($parts[0]) : 0;
  return ($year >= 2014) ? ($year - 2014 + 33) . "기" : "-";
}

// 회원정보 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
  $error_message = null;
  
  // 이메일 검증
  $email = trim($_POST['email']);
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_message = "올바른 이메일 형식이 아닙니다.";
  }
  
  // 학번 검증 (8자리 숫자)
  $student_id = trim($_POST['student_id']);
  if (!preg_match('/^\d{8}$/', $student_id)) {
    $error_message = "학번은 8자리 숫자여야 합니다.";
  }
  
  // 전화번호 검증 (010-XXXX-XXXX 형식)
  $mobile = trim($_POST['mobile']);
  if (!preg_match('/^010-\d{3,4}-\d{4}$/', $mobile)) {
    $error_message = "전화번호는 010-XXXX-XXXX 형식이어야 합니다.";
  }
  
  $dept = trim($_POST['dept']);
  $instrument = $_POST['instrument'];
  
  // 입부 년도 검증 (4자리 숫자)
  $join_year = trim($_POST['join_year']);
  if (!preg_match('/^\d{4}$/', $join_year)) {
    $error_message = "입부 년도는 4자리 숫자여야 합니다.";
  }
  
  $join_sem = $_POST['join_sem'] ?? '';
  $join_year_sem = '';
  
  if (!empty($join_year) && !empty($join_sem)) {
    $join_year_sem = $join_year . '-' . $join_sem;
  }
  
  $existing_img = $user['profile_img'];
  
  // 비밀번호 변경 검증
  $password_update = '';
  if (!empty($_POST['new_password'])) {
    // 비밀번호 길이 검증 (8자 이상)
    if (strlen($_POST['new_password']) < 8) {
      $error_message = "비밀번호는 8자 이상이어야 합니다.";
    } 
    // 비밀번호 확인 일치 검증
    else if ($_POST['new_password'] !== $_POST['confirm_password']) {
      $error_message = "새 비밀번호가 일치하지 않습니다.";
    } else {
      $password_update = ", password = '" . password_hash($_POST['new_password'], PASSWORD_DEFAULT) . "'";
    }
  }

  // 이미지 업로드 처리
  $profile_img = $existing_img;
  if (!empty($_FILES['profile_img']['name']) && is_uploaded_file($_FILES['profile_img']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION));
    
    // 확장자 검증
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
      $filename = uniqid("profile_") . "." . $ext;
      $target = $profile_img_path . $filename;
      
      // 파일 업로드 시도
      if (move_uploaded_file($_FILES['profile_img']['tmp_name'], $target)) {
        // 새 이미지 파일명으로만 업데이트 (기존 파일 삭제 코드 제거)
        $profile_img = $filename;
      } else {
        $error_message = "이미지 업로드 중 오류가 발생했습니다.";
      }
    } else {
      $error_message = "지원되지 않는 이미지 형식입니다. JPG, PNG, WEBP만 가능합니다.";
    }
  }
  
  // DB 업데이트
  if (!isset($error_message)) {
    $update_query = "UPDATE users SET 
                    email = ?, 
                    student_id = ?, 
                    dept = ?, 
                    mobile = ?, 
                    instrument = ?, 
                    join_year_sem = ?, 
                    profile_img = ? 
                    $password_update
                    WHERE id = ?";
    
    $stmt = $pdo->prepare($update_query);
    $stmt->execute([$email, $student_id, $dept, $mobile, $instrument, $join_year_sem, $profile_img, $user_id]);
    
    $success_message = "회원정보가 성공적으로 수정되었습니다.";
    
    // 업데이트된 사용자 정보 다시 가져오기
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
  }
}

// 이미지 삭제 처리 (기본 이미지로 변경만 하고 파일은 삭제하지 않음)
if (isset($_POST['delete_image'])) {
  // 기본 이미지 설정
  $default_img = 'profile_6800f3c5a647f.png'; // 기본 이미지 파일명
  
  // DB 업데이트 (파일 삭제 코드 제거)
  $stmt = $pdo->prepare("UPDATE users SET profile_img = ? WHERE id = ?");
  $stmt->execute([$default_img, $user_id]);
  
  // 업데이트된 사용자 정보 다시 가져오기
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$user_id]);
  $user = $stmt->fetch();
  
  $success_message = "프로필 이미지가 기본 이미지로 변경되었습니다.";
}

require_once 'header.php';
?>

<style>
.mypage-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 140px 1.5rem 4rem;
}

.profile-header {
  text-align: center;
  margin-bottom: 3rem;
}

.profile-header h2 {
  font-size: 2rem;
  font-weight: 700;
  color: #333;
  margin-bottom: 0.5rem;
}

.profile-header p {
  color: #666;
  font-size: 1.1rem;
}

.profile-section {
  background: #fff;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 2.5rem;
  margin-bottom: 2rem;
}

.profile-flex {
  display: flex;
  gap: 2.5rem;
  align-items: flex-start;
}

.profile-image-section {
  flex: 0 0 160px;
  text-align: center;
}

.profile-image {
  width: 160px;
  height: 160px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid #f0f0f0;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  margin-bottom: 1rem;
}

.profile-image-default {
  width: 160px;
  height: 160px;
  border-radius: 50%;
  background: #eee;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
  color: #aaa;
  border: 4px solid #f0f0f0;
  margin-bottom: 1rem;
}

.profile-info {
  flex: 1;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

.info-item {
  margin-bottom: 0.5rem;
}

.info-label {
  font-weight: 600;
  color: #555;
  margin-bottom: 0.3rem;
  display: block;
  font-size: 0.9rem;
}

.info-value {
  font-size: 1rem;
  color: #333;
  padding: 0.5rem 0;
  border-bottom: 1px solid #eee;
}

.edit-button {
  background: var(--blue);
  color: white;
  border: none;
  padding: 0.8rem 1.5rem;
  border-radius: var(--radius);
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
  margin-top: 1.5rem;
}

.edit-button:hover {
  background: #0051d6;
}

.edit-form {
  margin-top: 2rem;
  display: none;
}

.edit-form.active {
  display: block;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 600;
  color: #555;
  font-size: 0.9rem;
}

.form-input {
  padding: 0.8rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-input:focus {
  border-color: var(--blue);
  outline: none;
  box-shadow: 0 0 0 2px rgba(0,100,255,0.2);
}

.form-submit-row {
  margin-top: 2rem;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.form-note {
  font-size: 0.85rem;
  color: #666;
  margin-top: 0.3rem;
}

.password-section {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #eee;
}

.password-section h3 {
  font-size: 1.2rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  color: #333;
}

.alert {
  padding: 1rem;
  border-radius: 8px;
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

.form-file-input {
  margin-top: 0.5rem;
}

.img-actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.img-delete-btn {
  background: #f8d7da;
  color: #dc3545;
  border: none;
  padding: 0.5rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.85rem;
  transition: background 0.2s;
}

.img-delete-btn:hover {
  background: #f5c6cb;
}

@media (max-width: 768px) {
  .profile-flex {
    flex-direction: column;
    align-items: center;
  }
  
  .profile-image-section {
    margin-bottom: 2rem;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .form-submit-row {
    flex-direction: column;
  }
  
  .form-submit-row button {
    width: 100%;
  }
}
</style>

<div class="mypage-container">
  <div class="profile-header">
    <h2>마이페이지</h2>
    <p><?= htmlspecialchars($user['name']) ?>님의 회원 정보</p>
  </div>
  
  <?php if (isset($success_message)): ?>
  <div class="alert alert-success">
    <?= $success_message ?>
  </div>
  <?php endif; ?>
  
  <?php if (isset($error_message)): ?>
  <div class="alert alert-danger">
    <?= $error_message ?>
  </div>
  <?php endif; ?>
  
  <div class="profile-section">
    <div class="profile-flex">
      <div class="profile-image-section">
        <?php if ($user['profile_img']): ?>
          <img src="<?= $profile_img_dir . htmlspecialchars($user['profile_img']) ?>" alt="프로필 이미지" class="profile-image">
        <?php else: ?>
          <div class="profile-image-default">👤</div>
        <?php endif; ?>
      </div>
      
      <div class="profile-info">
        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">이름</span>
            <div class="info-value"><?= htmlspecialchars($user['name']) ?></div>
          </div>
          
          <div class="info-item">
            <span class="info-label">기수</span>
            <div class="info-value"><?= calculateGeneration($user['join_year_sem']) ?></div>
          </div>
          
          <div class="info-item">
            <span class="info-label">학번</span>
            <div class="info-value"><?= htmlspecialchars($user['student_id']) ?></div>
          </div>
          
          <div class="info-item">
            <span class="info-label">학과</span>
            <div class="info-value"><?= htmlspecialchars($user['dept']) ?></div>
          </div>
          
          <div class="info-item">
            <span class="info-label">악기</span>
            <div class="info-value"><?= htmlspecialchars($user['instrument']) ?></div>
          </div>
          
          <div class="info-item">
            <span class="info-label">입부 년도/학기</span>
            <div class="info-value"><?= htmlspecialchars($user['join_year_sem']) ?></div>
          </div>
          
          <div class="info-item">
            <span class="info-label">전화번호</span>
            <div class="info-value"><?= htmlspecialchars($user['mobile']) ?></div>
          </div>
          
          <div class="info-item">
            <span class="info-label">이메일</span>
            <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
          </div>
          
          <?php if($user['position']): ?>
          <div class="info-item">
            <span class="info-label">직책</span>
            <div class="info-value"><?= htmlspecialchars($user['position']) ?></div>
          </div>
          <?php endif; ?>
          
          <?php if($user['is_leader']): ?>
          <div class="info-item">
            <span class="info-label">파트장 여부</span>
            <div class="info-value">✓</div>
          </div>
          <?php endif; ?>
        </div>
        
        <button class="edit-button" id="edit-profile-btn">회원정보 수정</button>
      </div>
    </div>
    
    <form method="post" enctype="multipart/form-data" class="edit-form" id="edit-form">
      <input type="hidden" name="update_profile" value="1">
      
      <div class="form-grid">
        <div class="form-group">
          <label for="email">이메일</label>
          <input type="email" id="email" name="email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
          <span class="form-note">유효한 이메일 주소를 입력해주세요.</span>
        </div>
        
        <div class="form-group">
          <label for="student_id">학번</label>
          <input type="text" id="student_id" name="student_id" class="form-input" value="<?= htmlspecialchars($user['student_id']) ?>" required pattern="[0-9]{8}" maxlength="8">
          <span class="form-note">8자리 숫자로 입력해주세요.</span>
        </div>
        
        <div class="form-group">
          <label for="dept">학과</label>
          <input type="text" id="dept" name="dept" class="form-input" value="<?= htmlspecialchars($user['dept']) ?>" required>
        </div>
        
        <div class="form-group">
          <label for="mobile">전화번호</label>
          <input type="text" id="mobile" name="mobile" class="form-input" value="<?= htmlspecialchars($user['mobile']) ?>" required pattern="010-[0-9]{3,4}-[0-9]{4}">
          <span class="form-note">010-XXXX-XXXX 형식으로 입력해주세요.</span>
        </div>
        
        <div class="form-group">
          <label for="instrument">악기</label>
          <?php
            // 현재 사용자의 악기를 가져옴
            $current_instrument = $user['instrument'];
            
            // 악기 카테고리 정의
            $instrument_categories = [
              '현악기' => ['바이올린', '비올라', '첼로', '콘트라베이스'],
              '목관악기' => ['피콜로', '플루트', '오보에', '클라리넷', '바순', '색소폰'],
              '금관악기' => ['호른', '트럼펫', '트롬본', '튜바'],
              '타악기' => ['팀파니', '스네어드럼', '베이스드럼', '심벌', '마림바', '비브라폰'],
              '기타' => ['피아노', '하프', '기타']
            ];
          ?>
          <select class="form-input" id="instrument" name="instrument" required>
            <option value="">악기 선택</option>
            <?php foreach($instrument_categories as $category => $instruments): ?>
              <optgroup label="<?= $category ?>">
                <?php foreach($instruments as $instrument): ?>
                  <option value="<?= $instrument ?>" <?= $current_instrument == $instrument ? 'selected' : '' ?>><?= $instrument ?></option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label for="join_year">입부 년도/학기</label>
          <?php
            // 입부 년도와 학기 분리
            $join_parts = explode('-', $user['join_year_sem']);
            $join_year = isset($join_parts[0]) ? $join_parts[0] : '';
            $join_sem = isset($join_parts[1]) ? $join_parts[1] : '';
          ?>
          <div style="display: flex; gap: 1rem;">
            <div style="flex: 1;">
              <input type="text" id="join_year" name="join_year" class="form-input" value="<?= htmlspecialchars($join_year) ?>" pattern="[0-9]{4}" maxlength="4" required placeholder="연도(4자리)">
            </div>
            <select class="form-input" id="join_sem" name="join_sem" required style="flex: 1;">
              <option value="">학기 선택</option>
              <option value="1" <?= $join_sem == '1' ? 'selected' : '' ?>>1학기</option>
              <option value="2" <?= $join_sem == '2' ? 'selected' : '' ?>>2학기</option>
            </select>
          </div>
          <span class="form-note">입부 년도는 4자리 숫자로 입력해주세요. (예: 2023)</span>
        </div>
        
        <div class="form-group">
          <label for="profile_img">프로필 이미지</label>
          <input type="file" id="profile_img" name="profile_img" class="form-file-input" accept="image/*">
          <span class="form-note">JPG, PNG, WEBP 파일만 가능합니다.</span>
          
          <?php if($user['profile_img']): ?>
          <div class="img-actions">
            <button type="submit" name="delete_image" class="img-delete-btn">기본 이미지로 변경</button>
          </div>
          <?php endif; ?>
        </div>
      </div>
      
      <div class="password-section">
        <h3>비밀번호 변경</h3>
        <div class="form-grid">
          <div class="form-group">
            <label for="new_password">새 비밀번호</label>
            <input type="password" id="new_password" name="new_password" class="form-input" minlength="8" pattern=".{8,}">
            <span class="form-note">8자 이상 입력해주세요. 변경하지 않으려면 비워두세요.</span>
          </div>
          
          <div class="form-group">
            <label for="confirm_password">새 비밀번호 확인</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-input" minlength="8">
            <span class="form-note">비밀번호를 한번 더 입력해주세요.</span>
          </div>
        </div>
      </div>
      
      <div class="form-submit-row">
        <button type="button" class="edit-button" style="background: #6c757d;" id="cancel-edit-btn">취소</button>
        <button type="submit" class="edit-button">정보 수정하기</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const editBtn = document.getElementById('edit-profile-btn');
  const cancelBtn = document.getElementById('cancel-edit-btn');
  const editForm = document.getElementById('edit-form');
  
  editBtn.addEventListener('click', function() {
    editForm.classList.add('active');
    editBtn.style.display = 'none';
  });
  
  cancelBtn.addEventListener('click', function() {
    editForm.classList.remove('active');
    editBtn.style.display = 'block';
  });
  
  // 비밀번호 확인 검증
  const form = document.querySelector('form');
  form.addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword && newPassword !== '') {
      e.preventDefault();
      alert('새 비밀번호가 일치하지 않습니다.');
    }
    
    if (newPassword !== '' && newPassword.length < 8) {
      e.preventDefault();
      alert('비밀번호는 8자 이상이어야 합니다.');
    }
  });
  
  // 전화번호 자동 포맷팅
  document.querySelector('input[name="mobile"]').addEventListener('input', e => {
    let v = e.target.value.replace(/[^0-9]/g, '').slice(0, 11);
    if(v.length > 10)      v = v.replace(/(\d{3})(\d{4})(\d{4})/, '$1-$2-$3');
    else if(v.length > 7)  v = v.replace(/(\d{3})(\d{3,4})(\d{0,4})/, '$1-$2-$3');
    else if(v.length > 3)  v = v.replace(/(\d{3})(\d{0,4})/, '$1-$2');
    e.target.value = v;
  });
});
</script>

<?php require_once 'footer.php'; ?>