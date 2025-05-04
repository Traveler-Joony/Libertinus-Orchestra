<?php
require_once 'config.php';
require_once 'functions.php';

// 로그인 체크
if (!is_logged_in()) {
  echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
  exit;
}

// 게시판 ID 확인
$board_id = isset($_GET['board_id']) ? intval($_GET['board_id']) : 0;
if (!$board_id) {
  header("Location: community.php");
  exit;
}

// 게시판 정보 가져오기
$stmt = $pdo->prepare("SELECT * FROM community_boards WHERE id = ?");
$stmt->execute([$board_id]);
$board = $stmt->fetch();

if (!$board) {
  header("Location: community.php");
  exit;
}

// 권한 체크
if (!can_write_post($board_id)) {
  echo "<script>alert('글을 작성할 권한이 없습니다.'); history.back();</script>";
  exit;
}

$page_title = '글쓰기 - ' . $board['name'];
include 'header.php';
?>

<style>
.write-section {
  padding: 2rem 0;
  max-width: 900px;
  margin: 0 auto;
  padding-top: calc(clamp(70px, 12vh, 120px) + 2rem);
}

.write-container {
  background: #fff;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 2rem;
  margin-bottom: 2rem;
}

.write-header {
  margin-bottom: 2rem;
  text-align: center;
}

.write-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #333;
}

.write-form-group {
  margin-bottom: 1.5rem;
}

.write-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #333;
}

.write-input {
  width: 100%;
  padding: 0.8rem 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.write-textarea {
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

.write-checkbox {
  display: flex;
  align-items: center;
}

.write-checkbox input[type="checkbox"] {
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
  cursor<?php
require_once 'config.php';
require_once 'functions.php';

// 로그인 체크
if (!is_logged_in()) {
  echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
  exit;
}

// 게시판 ID 확인
$board_id = isset($_GET['board_id']) ? intval($_GET['board_id']) : 0;
if (!$board_id) {
  header("Location: community.php");
  exit;
}

// 게시판 정보 가져오기
$stmt = $pdo->prepare("SELECT * FROM community_boards WHERE id = ?");
$stmt->execute([$board_id]);
$board = $stmt->fetch();

if (!$board) {
  header("Location: community.php");
  exit;
}

// 권한 체크
if (!can_write_post($board_id)) {
  echo "<script>alert('글을 작성할 권한이 없습니다.'); history.back();</script>";
  exit;
}

$page_title = '글쓰기 - ' . $board['name'];
include 'header.php';
?>

<style>
.write-section {
  padding: 2rem 0;
  max-width: 900px;
  margin: 0 auto;
  padding-top: calc(clamp(70px, 12vh, 120px) + 2rem);
}

.write-container {
  background: #fff;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 2rem;
  margin-bottom: 2rem;
}

.write-header {
  margin-bottom: 2rem;
  text-align: center;
}

.write-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #333;
}

.write-form-group {
  margin-bottom: 1.5rem;
}

.write-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #333;
}

.write-input {
  width: 100%;
  padding: 0.8rem 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.write-textarea {
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

.write-checkbox {
  display: flex;
  align-items: center;
}

.write-checkbox input[type="checkbox"] {
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

.write-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-top: 2rem;
}

.write-btn {
  padding: 0.8rem 2rem;
  border-radius: 4px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  font-size: 1rem;
}

.write-submit {
  background-color: var(--blue);
  color: white;
}

.write-submit:hover {
  background-color: #0051d6;
}

.write-cancel {
  background-color: #f8f9fa;
  color: #555;
}

.write-cancel:hover {
  background-color: #e9ecef;
}

/* 파일 첨부 관련 스타일 */
.file-upload-container {
  border: 2px dashed #ddd;
  border-radius: 4px;
  padding: 2rem;
  text-align: center;
  margin-bottom: 1rem;
  cursor: pointer;
  transition: all 0.2s;
}

.file-upload-container:hover {
  border-color: var(--blue);
  background-color: #f8f9fa;
}

.file-upload-icon {
  font-size: 2.5rem;
  color: #aaa;
  margin-bottom: 1rem;
}

.file-upload-text {
  font-size: 1rem;
  color: #666;
  margin-bottom: 0.5rem;
}

.file-upload-subtext {
  font-size: 0.85rem;
  color: #aaa;
}

.file-upload-input {
  display: none;
}

.file-list {
  margin-top: 1rem;
}

.file-item {
  display: flex;
  align-items: center;
  padding: 0.5rem;
  border: 1px solid #eee;
  border-radius: 4px;
  margin-bottom: 0.5rem;
  background-color: #f8f9fa;
}

.file-icon {
  margin-right: 0.75rem;
  font-size: 1.25rem;
  color: #555;
}

.file-info {
  flex: 1;
}

.file-name {
  font-size: 0.9rem;
  font-weight: 500;
  color: #333;
  margin-bottom: 0.2rem;
}

.file-size {
  font-size: 0.8rem;
  color: #888;
}

.file-remove {
  padding: 0.4rem;
  color: #dc3545;
  cursor: pointer;
  transition: all 0.2s;
}

.file-remove:hover {
  color: #c82333;
}

/* 탭 스타일 */
.upload-tabs {
  display: flex;
  border-bottom: 1px solid #ddd;
  margin-bottom: 1rem;
}

.upload-tab {
  padding: 0.75rem 1.5rem;
  cursor: pointer;
  font-weight: 500;
  color: #555;
  transition: all 0.2s;
}

.upload-tab:hover {
  background-color: #f8f9fa;
}

.upload-tab.active {
  color: var(--blue);
  border-bottom: 2px solid var(--blue);
}

.upload-content {
  display: none;
}

.upload-content.active {
  display: block;
}
</style>

<main class="write-section container">
  <div class="write-container">
    <div class="write-header">
      <h2 class="write-title"><?= htmlspecialchars($board['name']) ?> - 새 글 작성</h2>
    </div>
    
    <form action="community_process.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="write">
      <input type="hidden" name="board_id" value="<?= $board_id ?>">
      
      <div class="write-form-group">
        <label for="title" class="write-label">제목</label>
        <input type="text" id="title" name="title" class="write-input" placeholder="제목을 입력하세요." required>
      </div>
      
      <div class="write-form-group">
        <label for="content" class="write-label">내용</label>
        <textarea id="content" name="content" class="write-textarea" placeholder="내용을 입력하세요." required></textarea>
      </div>
      
      <div class="write-form-group">
        <label class="write-label">첨부파일</label>
        
        <div class="upload-tabs">
          <div class="upload-tab active" data-tab="image">이미지</div>
          <?php if (is_admin()): ?>
          <div class="upload-tab" data-tab="file">파일</div>
          <?php endif; ?>
        </div>
        
        <!-- 이미지 업로드 탭 -->
        <div class="upload-content active" id="imageUploadTab">
          <div class="file-upload-container" id="imageUploadContainer">
            <div class="file-upload-icon">
              <i class="fas fa-cloud-upload-alt"></i>
            </div>
            <div class="file-upload-text">이미지를 여기에 끌어다 놓거나 클릭하여 업로드하세요.</div>
            <div class="file-upload-subtext">최대 5개, 파일당 최대 5MB (JPG, PNG, GIF, WEBP)</div>
            <input type="file" id="imageInput" name="images[]" class="file-upload-input" accept="image/*" multiple>
          </div>
          
          <div class="image-preview-container" id="image-preview-container"></div>
        </div>
        
        <?php if (is_admin()): ?>
        <!-- 파일 업로드 탭 (관리자만 가능) -->
        <div class="upload-content" id="fileUploadTab">
          <div class="file-upload-container" id="fileUploadContainer">
            <div class="file-upload-icon">
              <i class="fas fa-file-upload"></i>
            </div>
            <div class="file-upload-text">파일을 여기에 끌어다 놓거나 클릭하여 업로드하세요.</div>
            <div class="file-upload-subtext">최대 5개, 파일당 최대 20MB (PDF, DOC, XLS, PPT, ZIP 등)</div>
            <input type="file" id="fileInput" name="files[]" class="file-upload-input" multiple>
          </div>
          
          <div class="file-list" id="file-list-container"></div>
        </div>
        <?php endif; ?>
      </div>
      
      <?php if (is_admin()): ?>
      <div class="write-form-group">
        <label class="write-checkbox">
          <input type="checkbox" name="is_notice" value="1">
          <span>공지글로 등록</span>
        </label>
      </div>
      <?php endif; ?>
      
      <div class="write-actions">
        <button type="button" class="write-btn write-cancel" onclick="history.back()">취소</button>
        <button type="submit" class="write-btn write-submit">등록하기</button>
      </div>
    </form>
  </div>
</main>

<script>
// 탭 전환
document.querySelectorAll('.upload-tab').forEach(tab => {
  tab.addEventListener('click', function() {
    // 모든 탭 비활성화
    document.querySelectorAll('.upload-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.upload-content').forEach(c => c.classList.remove('active'));
    
    // 현재 탭 활성화
    this.classList.add('active');
    document.getElementById(this.dataset.tab + 'UploadTab').classList.add('active');
  });
});

// 이미지 업로드 클릭 연동
document.getElementById('imageUploadContainer').addEventListener('click', function() {
  document.getElementById('imageInput').click();
});

// 이미지 파일 변경 이벤트
document.getElementById('imageInput').addEventListener('change', function() {
  previewImages(this);
});

// 이미지 드래그 앤 드롭 이벤트
const imageUploadContainer = document.getElementById('imageUploadContainer');
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
  imageUploadContainer.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
  e.preventDefault();
  e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
  imageUploadContainer.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
  imageUploadContainer.addEventListener(eventName, unhighlight, false);
});

function highlight() {
  imageUploadContainer.classList.add('highlight');
}

function unhighlight() {
  imageUploadContainer.classList.remove('highlight');
}

imageUploadContainer.addEventListener('drop', handleImageDrop, false);

function handleImageDrop(e) {
  const dt = e.dataTransfer;
  const files = dt.files;
  document.getElementById('imageInput').files = files;
  previewImages(document.getElementById('imageInput'));
}

// 이미지 미리보기 스크립트
function previewImages(input) {
  const container = document.getElementById('image-preview-container');
  container.innerHTML = '';
  
  // 파일 선택이 취소되었을 경우
  if (!input.files || input.files.length === 0) return;
  
  // 최대 5개까지만 미리보기
  const maxFiles = 5;
  const files = Array.from(input.files).slice(0, maxFiles);
  
  if (input.files.length > maxFiles) {
    alert(`이미지는 최대 ${maxFiles}개까지 첨부할 수 있습니다.`);
  }
  
  files.forEach((file, index) => {
    // 이미지 타입 체크
    if (!file.type.match('image.*')) {
      alert(`${file.name}은(는) 이미지 파일이 아닙니다.`);
      return;
    }
    
    // 파일 크기 체크 (5MB 제한)
    if (file.size > 5 * 1024 * 1024) {
      alert(`${file.name}의 크기가 5MB를 초과합니다.`);
      return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
      const preview = document.createElement('div');
      preview.className = 'image-preview';
      preview.innerHTML = `
        <img src="${e.target.result}" alt="이미지 미리보기">
        <div class="image-preview-remove" onclick="removeImage(this, ${index})">×</div>
      `;
      container.appendChild(preview);
    };
    reader.readAsDataURL(file);
  });
}

// 이미지 미리보기 제거
function removeImage(button, index) {
  const container = button.closest('.image-preview-container');
  const preview = button.closest('.image-preview');
  container.removeChild(preview);
  
  // 파일 입력에서 해당 파일 제거 (새 FileList 만들기는 불가능하므로 입력창 초기화)
  document.getElementById('imageInput').value = '';
  
  // 남아있는 이미지들을 기반으로 새 FormData 만들기
  const remainingImages = container.querySelectorAll('img');
  if (remainingImages.length > 0) {
    alert('이미지를 모두 제거 후 다시 선택해주세요.');
  }
}

<?php if (is_admin()): ?>
// 파일 업로드 관련 스크립트 (관리자만)
document.getElementById('fileUploadContainer').addEventListener('click', function() {
  document.getElementById('fileInput').click();
});

document.getElementById('fileInput').addEventListener('change', function() {
  handleFiles(this.files);
});

const fileUploadContainer = document.getElementById('fileUploadContainer');
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
  fileUploadContainer.addEventListener(eventName, preventDefaults, false);
});

['dragenter', 'dragover'].forEach(eventName => {
  fileUploadContainer.addEventListener(eventName, highlightFile, false);
});

['dragleave', 'drop'].forEach(eventName => {
  fileUploadContainer.addEventListener(eventName, unhighlightFile, false);
});

function highlightFile() {
  fileUploadContainer.classList.add('highlight');
}

function unhighlightFile() {
  fileUploadContainer.classList.remove('highlight');
}

fileUploadContainer.addEventListener('drop', handleFileDrop, false);

function handleFileDrop(e) {
  const dt = e.dataTransfer;
  const files = dt.files;
  document.getElementById('fileInput').files = files;
  handleFiles(files);
}

function handleFiles(files) {
  const container = document.getElementById('file-list-container');
  container.innerHTML = '';
  
  // 최대 5개까지만 처리
  const maxFiles = 5;
  const fileArray = Array.from(files).slice(0, maxFiles);
  
  if (files.length > maxFiles) {
    alert(`파일은 최대 ${maxFiles}개까지 첨부할 수 있습니다.`);
  }
  
  fileArray.forEach((file, index) => {
    // 파일 크기 체크 (20MB 제한)
    if (file.size > 20 * 1024 * 1024) {
      alert(`${file.name}의 크기가 20MB를 초과합니다.`);
      return;
    }
    
    // 파일 확장자 가져오기
    const extension = file.name.split('.').pop().toLowerCase();
    
    // 아이콘 클래스 결정
    const iconClass = getFileIconClass(extension);
    
    // 파일 크기 포맷팅
    const formattedSize = formatFileSize(file.size);
    
    // 파일 아이템 생성
    const fileItem = document.createElement('div');
    fileItem.className = 'file-item';
    fileItem.innerHTML = `
      <div class="file-icon">
        <i class="fas ${iconClass}"></i>
      </div>
      <div class="file-info">
        <div class="file-name">${file.name}</div>
        <div class="file-size">${formattedSize}</div>
      </div>
      <div class="file-remove" onclick="removeFile(this, ${index})">
        <i class="fas fa-times"></i>
      </div>
    `;
    container.appendChild(fileItem);
  });
}

// 파일 제거
function removeFile(button, index) {
  const container = button.closest('.file-list');
  const fileItem = button.closest('.file-item');
  container.removeChild(fileItem);
  
  // 파일 입력에서 해당 파일 제거 (새 FileList 만들기는 불가능하므로 입력창 초기화)
  document.getElementById('fileInput').value = '';
  
  // 남아있는 파일들을 기반으로 새 FormData 만들기
  const remainingFiles = container.querySelectorAll('.file-item');
  if (remainingFiles.length > 0) {
    alert('파일을 모두 제거 후 다시 선택해주세요.');
  }
}

// 파일 아이콘 클래스 반환
function getFileIconClass(extension) {
  switch (extension) {
    case 'pdf':
      return 'fa-file-pdf';
    case 'doc':
    case 'docx':
      return 'fa-file-word';
    case 'xls':
    case 'xlsx':
      return 'fa-file-excel';
    case 'ppt':
    case 'pptx':
      return 'fa-file-powerpoint';
    case 'zip':
    case 'rar':
    case '7z':
      return 'fa-file-archive';
    case 'jpg':
    case 'jpeg':
    case 'png':
    case 'gif':
    case 'webp':
      return 'fa-file-image';
    case 'mp3':
    case 'wav':
    case 'ogg':
      return 'fa-file-audio';
    case 'mp4':
    case 'avi':
    case 'mov':
      return 'fa-file-video';
    case 'txt':
      return 'fa-file-alt';
    default:
      return 'fa-file';
  }
}

// 파일 크기 포맷팅
function formatFileSize(size) {
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  let i = 0;
  
  while (size >= 1024 && i < units.length - 1) {
    size /= 1024;
    i++;
  }
  
  return size.toFixed(2) + ' ' + units[i];
}
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>