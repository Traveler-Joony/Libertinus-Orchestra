<?php
$page_title = '입단 지원';
require_once 'config.php';
require_once 'functions.php';

$errors = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf_token($_POST['csrf_token'] ?? '')) $errors[] = '잘못된 접근입니다.';
  $d = fn($k) => sanitize($_POST[$k] ?? '');

  $data = [
    'name' => $d('name'),
    'birth_year' => $d('birth_year'),
    'birth_month' => $_POST['birth_month'] ?? '',
    'birth_day' => $d('birth_day'),
    'email' => $d('email'),
    'student_id' => $d('student_id'),
    'dept' => $d('dept'),
    'mobile' => $d('mobile'),
    'instrument' => $_POST['instrument'] ?? '',
    'message' => $d('message'),
  ];

  // 유효성 검사
  if (!$data['name']) $errors[] = '이름을 입력하세요.';
  if (!preg_match('/^\d{4}$/', $data['birth_year'])) $errors[] = '출생연도는 4자리로 입력하세요.';
  if (!preg_match('/^\d{1,2}$/', $data['birth_day']) || $data['birth_day'] < 1 || $data['birth_day'] > 31) $errors[] = '생일 형식이 올바르지 않습니다.';
  if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = '이메일 형식이 올바르지 않습니다.';
  if (!preg_match('/^\d{8}$/', $data['student_id'])) $errors[] = '학번은 8자리로 입력하세요.';
  if (!$data['dept']) $errors[] = '학과를 입력하세요.';
  if (!preg_match('/^\d{3}-\d{3,4}-\d{4}$/', $data['mobile'])) $errors[] = '전화번호 형식이 올바르지 않습니다.';
  if (!$data['instrument']) $errors[] = '악기를 선택하세요.';

  if (!$errors) {
    $birth = "{$data['birth_year']}-{$data['birth_month']}-{$data['birth_day']}";
    $stmt = $pdo->prepare("INSERT INTO applications (name, birth, email, student_id, dept, mobile, instrument, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
      $data['name'], $birth, $data['email'], $data['student_id'], $data['dept'],
      $data['mobile'], $data['instrument'], $data['message']
    ]);
    $message = '입단 지원이 완료되었습니다. 감사합니다!';
  }
}
include 'header.php';
?>

<style>
.contact-section {
  background-color: #f9f9f9;
  padding: 2rem 0 1rem;
  margin-bottom: 0;
  padding-top: calc(clamp(70px, 12vh, 120px) + 2rem);
}

.contact-card {
  background-color: white;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 2rem;
  margin-bottom: 0;
  text-align: center;
  max-width: 640px;
  margin-left: auto;
  margin-right: auto;
}

.contact-info {
  margin-bottom: 1.5rem;
}

.contact-info p {
  margin: 0.5rem 0;
  color: #555;
}

.social-links {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
  justify-content: center;
}

.social-links a {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  color: white;
  border-radius: 50%;
  text-decoration: none;
  transition: background-color 0.3s;
}

.social-links a:hover {
  opacity: 0.9;
}

.form-input {
  display: block;
  width: 100%;
  padding: 0.75rem;
  margin-bottom: 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.row-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 1rem;
}

.btn-join {
  display: inline-block;
  padding: 1rem 2rem;
  background-color: #3498db;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.3s;
}

.btn-join:hover {
  background-color: #2980b9;
}

.form-error {
  color: #e74c3c;
  margin-bottom: 1rem;
}

.form-success {
  color: #2ecc71;
  margin-bottom: 1rem;
}
</style>

<section class="contact-section">
  <div class="container">
    <div class="contact-card">
      <h2>연락처 및 SNS</h2>
      <div class="contact-info">
        <p><strong>주소:</strong> 충청남도 아산시 순천향로 22 학생회관 S301(신창면, 순천향대학교)</p>
        <p><strong>연락처:</strong> 010-4308-8948</p>
        <p><strong>이메일:</strong> libertinus.16@gmail.com</p>
      </div>
      
      <div class="social-links">
        <!-- 유튜브: 빨간색 배경, 흰색 아이콘 -->
        <a href="https://www.youtube.com/@libertinus2875" target="_blank" title="유튜브 채널" style="background-color: #FF0000;">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>
            <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon>
          </svg>
        </a>
        
        <!-- 카카오톡: 노란색 배경, 검정색 아이콘 -->
        <a href="https://pf.kakao.com/_KSAjC" target="_blank" title="카카오톡 채널" style="background-color: #FFE812;">
          <svg width="20" height="20" viewBox="0 0 2500 2500" style="fill: #000000;">
            <path d="M1250,351.6c-560.9,0-1015.6,358.5-1015.6,800.8c0,285.9,190.1,536.8,476.1,678.5c-15.6,53.7-100,345.2-103.3,368.1c0,0-2,17.2,9.1,23.8c11.1,6.6,24.2,1.5,24.2,1.5c32-4.5,370.5-242.3,429.1-283.6c58.5,8.3,118.8,12.6,180.4,12.6c560.9,0,1015.6-358.5,1015.6-800.8C2265.6,710.1,1810.9,351.6,1250,351.6L1250,351.6z"/>
          </svg>
        </a>
        
        <!-- 인스타그램: 그라데이션 배경, 흰색 아이콘 -->
        <a href="https://www.instagram.com/libertinus_sch" target="_blank" title="인스타그램" style="background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
          </svg>
        </a>
      </div>
    </div>
  </div>
</section>

<main class="join-wrap" style="padding-top: 0;">
  <form class="join-box" method="post" autocomplete="off">
    <h2>입단 지원</h2>

    <?php foreach ($errors as $e): ?>
      <p class="form-error"><?= $e; ?></p>
    <?php endforeach; ?>
    <?php if ($message): ?>
      <p class="form-success"><?= $message; ?></p>
    <?php endif; ?>

    <input class="form-input" name="name" placeholder="이름" required />

    <div class="row-3">
      <input class="form-input" name="birth_year" maxlength="4" placeholder="출생연도(YYYY)" required />
      <select class="form-input" name="birth_month" required>
        <option value="">월</option><?php for ($m = 1; $m <= 12; $m++) echo "<option>$m</option>"; ?>
      </select>
      <input class="form-input" name="birth_day" maxlength="2" placeholder="일" required />
    </div>

    <input class="form-input" type="email" name="email" placeholder="이메일" required />

    <div class="row-2">
      <input class="form-input" name="student_id" maxlength="8" placeholder="학번(8자리)" required />
      <input class="form-input" name="dept" placeholder="학과" required />
    </div>

    <input class="form-input" name="mobile" placeholder="전화번호(010-1234-5678)" required />

    <select class="form-input" name="instrument" required>
      <option value="">악기 선택</option>
      <optgroup label="현악기">
        <option>바이올린</option><option>비올라</option><option>첼로</option><option>콘트라베이스</option>
      </optgroup>
      <optgroup label="목관악기">
        <option>피콜로</option><option>플루트</option><option>오보에</option><option>클라리넷</option><option>바순</option><option>색소폰</option>
      </optgroup>
      <optgroup label="금관악기">
        <option>호른</option><option>트럼펫</option><option>트롬본</option><option>튜바</option>
      </optgroup>
      <optgroup label="타악기">
        <option>팀파니</option><option>스네어드럼</option><option>심벌</option><option>마림바</option>
      </optgroup>
      <optgroup label="기타">
        <option>피아노</option><option>하프</option><option>기타</option>
      </optgroup>
    </select>

    <textarea class="form-input" name="message" rows="4" placeholder="하고 싶은 말 (선택사항)"></textarea>

    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
    <button class="btn-join">지원하기</button>
  </form>
</main>

<?php include 'footer.php'; ?>

<script>
document.querySelector('input[name="mobile"]').addEventListener('input', e => {
  let v = e.target.value.replace(/[^0-9]/g, '').slice(0, 11);
  if (v.length > 10) v = v.replace(/(\d{3})(\d{4})(\d{4})/, '$1-$2-$3');
  else if (v.length > 7) v = v.replace(/(\d{3})(\d{3,4})(\d{0,4})/, '$1-$2-$3');
  else if (v.length > 3) v = v.replace(/(\d{3})(\d{0,4})/, '$1-$2');
  e.target.value = v;
});
</script>