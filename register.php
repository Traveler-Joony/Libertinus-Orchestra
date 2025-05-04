<?php
$page_title='회원가입';
require_once 'config.php';
require_once 'functions.php';

$errors=[];$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!verify_csrf_token($_POST['csrf_token']??'')) $errors[]='잘못된 접근입니다.';
  $d=function($k){return sanitize($_POST[$k]??'');};
  $data=[
    'name'=>$d('name'),
    'birth_year'=>$d('birth_year'),
    'birth_month'=>($_POST['birth_month']??''),
    'birth_day'=>$d('birth_day'),
    'username'=>$d('username'),
    'password'=>($_POST['password']??''),
    'password2'=>($_POST['password_confirm']??''),
    'email'=>$d('email'),
    'student_id'=>$d('student_id'),
    'dept'=>$d('dept'),
    'mobile'=>$d('mobile'),
    'instrument'=>($_POST['instrument']??''),
    'join_year'=>$d('join_year'),
    'join_sem'=>($_POST['join_semester']??''),
    'agree_terms'=>($_POST['agree_terms']??''),
    'agree_privacy'=>($_POST['agree_privacy']??'')
  ];

  /* --- validation --- */
  if(!$data['name'])$errors[]='이름 입력';
  if(!preg_match('/^\d{4}$/',$data['birth_year']))$errors[]='출생연도 4자리';
  if(!preg_match('/^\d{1,2}$/',$data['birth_day'])||$data['birth_day']<1||$data['birth_day']>31)$errors[]='생일 오류';
  if(!preg_match('/^[A-Za-z0-9_]{4,16}$/',$data['username']))$errors[]='아이디 규칙 위반';
  if(strlen($data['password'])<8)$errors[]='비밀번호 8자 이상';
  if($data['password']!==$data['password2'])$errors[]='비밀번호 확인 불일치';
  if(!filter_var($data['email'],FILTER_VALIDATE_EMAIL))$errors[]='이메일 형식';
  if(!preg_match('/^\d{8}$/',$data['student_id']))$errors[]='학번 8자리';
  if(!$data['dept'])$errors[]='학과 입력';
  if(!preg_match('/^\d{3}-\d{3,4}-\d{4}$/',$data['mobile']))$errors[]='전화번호 형식';
  if(!$data['instrument'])$errors[]='악기 선택';
  if(!preg_match('/^\d{4}$/',$data['join_year']))$errors[]='입부연도 4자리';
  if(!$data['join_sem'])$errors[]='입부학기 선택';
  if(!$data['agree_terms']||!$data['agree_privacy'])$errors[]='약관 동의 필요';

  // 아이디 중복 확인
  if(!$errors){
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username=?");
    $stmt->execute([$data['username']]);
    if ($stmt->fetch()) {
      $errors[]='이미 사용 중인 아이디';
    }
  }

  if(!$errors){
    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $birth = "{$data['birth_year']}-{$data['birth_month']}-{$data['birth_day']}";
    $join = "{$data['join_year']}-{$data['join_sem']}";
    $stmt = $pdo->prepare(
      "INSERT INTO users(username,password,name,birth,email,student_id,dept,mobile,instrument,join_year_sem)
       VALUES(?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
      $data['username'],$hash,$data['name'],$birth,$data['email'],
      $data['student_id'],$data['dept'],$data['mobile'],$data['instrument'],$join
    ]);
    $message='회원가입 완료! 로그인하세요.';
  }
}
include 'header.php';
?>

<main class="join-wrap">
  <form class="join-box" method="post" autocomplete="off">
    <h2>회원가입</h2>

    <?php foreach($errors as $e): ?><p class="form-error"><?= $e; ?></p><?php endforeach; ?>
    <?php if($message): ?><p class="form-success"><?= $message; ?></p><?php endif; ?>

    <input class="form-input" name="name" placeholder="이름" required />

    <div class="row-3">
      <input class="form-input" name="birth_year" maxlength="4" placeholder="출생연도(YYYY)" required />
      <select class="form-input" name="birth_month" required>
        <option value="">월</option><?php for($m=1;$m<=12;$m++)echo"<option>$m</option>";?>
      </select>
      <input class="form-input" name="birth_day" maxlength="2" placeholder="일" required />
    </div>

    <input class="form-input" name="username" placeholder="아이디" required />
    <input class="form-input" type="password" name="password" placeholder="비밀번호" required />
    <input class="form-input" type="password" name="password_confirm" placeholder="비밀번호 확인" required />

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
        <option>팀파니</option><option>스네어드럼</option><option>베이스드럼</option><option>심벌</option><option>마림바</option><option>비브라폰</option>
      </optgroup>
      <optgroup label="기타">
        <option>피아노</option><option>하프</option><option>기타</option>
      </optgroup>
    </select>

    <div class="row-2">
      <input class="form-input" name="join_year" maxlength="4" placeholder="입부연도(YYYY)" required />
      <select class="form-input" name="join_semester" required>
        <option value="">학기</option><option>1</option><option>2</option>
      </select>
    </div>

    <div class="agreements">
      <label><input type="checkbox" name="agree_terms" value="1" required /> 서비스 이용약관 동의(필수)</label>
      <label><input type="checkbox" name="agree_privacy" value="1" required /> 개인정보 처리·초상권 사용 동의(필수)</label>
    </div>

    <details class="terms-box">
      <summary>약관 전문 보기</summary>
      <?= nl2br(htmlspecialchars(file_get_contents(__DIR__.'/terms.txt'))); ?>
    </details>

    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
    <button class="btn-join">가입하기</button>
  </form>
</main>

<?php include 'footer.php'; ?>

<script>
document.querySelector('input[name="mobile"]').addEventListener('input', e => {
  let v = e.target.value.replace(/[^0-9]/g, '').slice(0, 11);
  if(v.length > 10)      v = v.replace(/(\d{3})(\d{4})(\d{4})/, '$1-$2-$3');
  else if(v.length > 7)  v = v.replace(/(\d{3})(\d{3,4})(\d{0,4})/, '$1-$2-$3');
  else if(v.length > 3)  v = v.replace(/(\d{3})(\d{0,4})/, '$1-$2');
  e.target.value = v;
});
</script>
