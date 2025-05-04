<?php
require_once 'config.php';
require_once 'functions.php';

// 로그인 체크
if (!is_logged_in()) {
  echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
  exit;
}

// 게시글 ID 확인
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$post_id) {
  header("Location: community.php");
  exit;
}

// 게시글 정보 가져오기
$stmt = $pdo->prepare("
  SELECT p.*, 
         b.name AS board_name
  FROM community_posts p
  JOIN community_boards b ON p.board_id = b.id
  WHERE p.id = ?
");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
  header("Location: community.php");
  exit;
}

// 작성자 확인 (관리자는 제외)
if ($_SESSION['user_id'] != $post['user_id'] && !is_admin()) {
  echo "<script>alert('권한이 없습니다.'); history.back();</script>";
  exit;
}

// 게시글 이미지 가져오기
$stmt = $pdo->prepare("SELECT * FROM community_post_images WHERE post_id = ? ORDER BY sort_order ASC");
$stmt->execute([$post_id]);
$images = $stmt->fetchAll();

$page_title = '글수정 - ' . $post['title'];
include 'header.php';
?>

<style>
.edit-section {
  padding: 2rem 0;
  max-width: 900px;
  margin: 0 auto;
  padding-top: calc(clamp(70px, 12vh, 120px) + 2rem);
}

.edit-container {
  background: #fff;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 2rem;
  margin-bottom: 2rem;
}

.edit-header {
  margin-bottom: 2rem;
  text-align: center;
}

.edit-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #333;
}

.edit-form-group {
  margin-bottom: 1.5rem;
}

.edit-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #333;
}

.edit-input {
  width: 100%;
  padding: 0.8rem 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.edit-textarea {
  width: 100%;
  padding: 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  resize: vertical;
  min-height: 300px;
  font-family: inherit;
  font-size: 1rem;
  line-height: 1.7;
}

.edit-checkbox {
  display: flex;
  align-items: center;
}

.edit-checkbox input[type="checkbox"] {
  margin-right: 0.5rem;
}

.image-preview-container {
  margin-top: 1rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 1rem;
}

.image-preview {
  width: 100%;
  aspect-ratio: 1 / 1;
  border: 1px solid #ddd;
  border-radius: 4px;
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.image-preview img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.image-preview-remove {
  position: absolute;
  top: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.8);
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-weight: bold;
  color: #dc3545;
  border-radius: 0 0 0 4px;
}

.current-images {
  margin-bottom: 1rem;
}

.current-images-title {
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #333;
}

.edit-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-top: 2rem;
}

.edit-btn {
  padding: 0.8rem 2rem;
  border-radius: 4px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  font-size: 1rem;
}

.edit-submit {
  background-color: var(--blue);
  color: white;
}

.edit-submit:hover {
  background-color: #0051d6;
}

.edit-cancel {
  background-color: #f8f9fa;
  color: #555;
}

.edit-cancel:hover {
  background-color: #e9ecef;
}

</style>

<main class="edit-section container">
  <div class="edit-container">
    <div class="edit-header">
      <h2 class="edit-title"><?= htmlspecialchars($post['board_name']) ?> - 글 수정</h2>
    </div>
    
    <form action="community_process.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= $post_id ?>">
      
      <div class="edit-form-group">
        <label for="title" class="edit-label">제목</label>
        <input type="text" id="title" name="title" class="edit-input" value="<?= htmlspecialchars($post['title']) ?>" required>
      </div>
      
      <div class="edit-form-group">
        <label for="content" class="edit-label">내용</label>
        <textarea id="content" name="content" class="edit-textarea" required><?= htmlspecialchars($post['content']) ?></textarea>
      </div>
      
      <?php if (!empty($images)): ?>
        <div class="edit-form-group">
          <div class="current-images">
            <div class="current-images-title">현재 첨부된 이미지</div>
            <div class="image-preview-container">
              <?php foreach ($images as $index => $image): ?>
                <div class="image-preview">
                  <img src="./img/community/<?= htmlspecialchars($image['image_path']) ?>" alt="첨부 이미지">
                  <div class="image-preview-remove" data-id="<?= $image['id'] ?>">×</div>
                  <input type="hidden" name="keep_images[]" value="<?= $image['id'] ?>">
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
      
      <div class="edit-form-group">
        <label for="images" class="edit-label">새 이미지 첨부 (최대 <?= 5 - count($images) ?>개)</label>
        <input type="file" id="images" name="images[]" class="edit-input" accept="image/*" multiple onchange="previewImages(this, <?= 5 - count($images) ?>)">
        <div class="image-preview-container" id="new-image-preview-container"></div>
      </div>
      
      <?php if (is_admin()): ?>
      <div class="edit-form-group">
        <label class="edit-checkbox">
          <input type="checkbox" name="is_notice" value="1" <?= $post['is_notice'] ? 'checked' : '' ?>>
          <span>공지글로 등록</span>
        </label>
      </div>
      <?php endif; ?>
      
      <div class="edit-actions">
        <button type="button" class="edit-btn edit-cancel" onclick="history.back()">취소</button>
        <button type="submit" class="edit-btn edit-submit">수정하기</button>
      </div>
    </form>
  </div>
</main>

<script>
// 이미지 미리보기 스크립트
function previewImages(input, maxAdditional) {
  const container = document.getElementById('new-image-preview-container');
  container.innerHTML = '';
  
  // 파일 선택이 취소되었을 경우
  if (!input.files || input.files.length === 0) return;
  
  // 최대 추가 파일 수 제한
  const files = Array.from(input.files).slice(0, maxAdditional);
  
  if (input.files.length > maxAdditional) {
    alert(`새 이미지는 최대 ${maxAdditional}개까지 첨부할 수 있습니다.`);
  }
  
  files.forEach((file, index) => {
    // 이미지 타입 체크
    if (!file.type.match('image.*')) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
      const preview = document.createElement('div');
      preview.className = 'image-preview';
      preview.innerHTML = `
        <img src="${e.target.result}" alt="이미지 미리보기">
        <div class="image-preview-remove" onclick="removeNewImage(this, ${index})">×</div>
      `;
      container.appendChild(preview);
    };
    reader.readAsDataURL(file);
  });
}

// 새 이미지 미리보기 제거
function removeNewImage(button, index) {
  const container = button.closest('.image-preview-container');
  const preview = button.closest('.image-preview');
  container.removeChild(preview);
}

// 기존 이미지 제거 처리
document.querySelectorAll('.current-images .image-preview-remove').forEach(button => {
  button.addEventListener('click', function() {
    const preview = this.closest('.image-preview');
    const imageId = this.getAttribute('data-id');
    
    // 숨겨진 input 요소를 찾아 삭제 대상으로 표시
    const keepInput = preview.querySelector('input[name="keep_images[]"]');
    keepInput.name = 'remove_images[]';
    
    // 시각적으로 삭제됨을 표시
    preview.style.opacity = '0.5';
    preview.style.borderColor = '#dc3545';
    this.textContent = '+'; // 삭제 취소 표시로 변경
    
    // 클릭 이벤트 변경 - 다시 클릭하면 취소할 수 있도록
    this.removeEventListener('click', arguments.callee);
    this.addEventListener('click', function() {
      keepInput.name = 'keep_images[]';
      preview.style.opacity = '1';
      preview.style.borderColor = '#ddd';
      this.textContent = '×';
      
      // 이벤트 다시 원래대로
      this.removeEventListener('click', arguments.callee);
      this.addEventListener('click', arguments.callee.caller);
    });
  });
});
</script>

<?php include 'footer.php'; ?>