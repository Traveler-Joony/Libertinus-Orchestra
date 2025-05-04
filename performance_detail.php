<?php
require_once 'config.php';
include 'header.php';
$id = $_GET['id'] ?? 0;

// 공연 정보 가져오기
$stmt = $pdo->prepare("SELECT * FROM performances WHERE id=?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) die('존재하지 않는 공연입니다');

// 공연 이미지 가져오기
$img_stmt = $pdo->prepare("SELECT * FROM performance_images WHERE performance_id = ? ORDER BY sort_order ASC");
$img_stmt->execute([$id]);
$images = $img_stmt->fetchAll();
?>

<style>
  .performance-detail {
    padding: 2rem 0;
  }
  .youtube-container {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
    max-width: 100%;
    margin: 2rem 0;
  }
  .youtube-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  }
  .gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
    margin: 2rem 0;
  }
  .gallery-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 3px 6px rgba(0,0,0,0.16);
    aspect-ratio: 4/3;
    cursor: pointer;
  }
  .gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
  }
  .gallery-item:hover img {
    transform: scale(1.05);
  }
  .featured-image {
    width: 100%;
    height: auto;
    max-height: none;
    object-fit: contain;
    border-radius: 8px;
    margin-bottom: 2rem;
  }
  .performance-content {
    margin-top: 2rem;
    line-height: 1.5;
    white-space: pre-wrap;
  }
  
  /* 이미지 모달 */
  .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.9);
    padding: 50px 0;
    box-sizing: border-box;
    overflow-y: auto;
  }
  .modal-content {
    display: block;
    margin: 0 auto;
    max-width: 90%;
    max-height: 90vh;
    object-fit: contain;
  }
  .close {
    position: fixed;
    top: 20px;
    right: 30px;
    color: #f1f1f1;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    z-index: 1001;
  }
  .modal-navigation {
    position: fixed;
    width: 100%;
    top: 50%;
    display: flex;
    justify-content: space-between;
    padding: 0 30px;
    box-sizing: border-box;
    z-index: 1001;
  }
  .modal-nav-btn {
    color: white;
    font-size: 30px;
    cursor: pointer;
    user-select: none;
    background: rgba(0,0,0,0.5);
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transform: translateY(-50%);
  }
  .modal-slides {
    display: none;
    text-align: center;
    height: 100%;
  }
</style>

<main class="container" style="padding-top:clamp(70px,12vh,120px); max-width:960px;">
  <article class="performance-detail">
    <h2 class="section-title"><?= htmlspecialchars($item['title']) ?></h2>
    <p class="card-meta">작성일: <?= substr($item['created_at'], 0, 10) ?><br><br></p>
    
    <!-- 대표 이미지 -->
    <?php if (!empty($item['featured_image'])): ?>
      <img src="/img/performance/<?= htmlspecialchars($item['featured_image']) ?>" 
           alt="<?= htmlspecialchars($item['title']) ?>" 
           class="featured-image"
           onerror="this.onerror=null; this.src='/img/logo.png';">
    <?php endif; ?>
    
    <!-- 공연 내용 -->
    <div class="performance-content">
        <?= htmlspecialchars($item['content']) ?>
    </div>
    
    <!-- 유튜브 영상 -->
    <?php if (!empty($item['youtube_url'])): 
      // YouTube URL에서 영상 ID 추출
      preg_match('/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $item['youtube_url'], $matches);
      $video_id = $matches[1] ?? null;
      
      if ($video_id): ?>
        <div class="youtube-container">
          <iframe src="https://www.youtube.com/embed/<?= $video_id ?>" 
                  frameborder="0" 
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                  allowfullscreen></iframe>
        </div>
      <?php endif; ?>
    <?php endif; ?>
    
    <!-- 이미지 갤러리 -->
    <?php if (count($images) > 0): ?>
      <h3>공연 사진</h3>
      <div class="gallery">
        <?php foreach ($images as $index => $image): ?>
          <div class="gallery-item" onclick="openModal();currentSlide(<?= $index + 1 ?>)">
            <img src="/img/performance/<?= htmlspecialchars($image['image_path']) ?>" 
                 alt="공연 이미지 <?= $index + 1 ?>">
          </div>
        <?php endforeach; ?>
      </div>
      
      <!-- 이미지 모달 -->
      <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        
        <div class="modal-navigation">
          <span class="modal-nav-btn prev" onclick="plusSlides(-1)">&#10094;</span>
          <span class="modal-nav-btn next" onclick="plusSlides(1)">&#10095;</span>
        </div>
        
        <?php foreach ($images as $index => $image): ?>
          <div class="modal-slides">
            <img src="/img/performance/<?= htmlspecialchars($image['image_path']) ?>" 
                 alt="공연 이미지 <?= $index + 1 ?>"
                 class="modal-content">
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    
    <!-- 목록으로 돌아가기 -->
    <div style="text-align: center; margin-top: 3rem;">
      <a href="performances.php" class="btn">목록으로 돌아가기</a>
    </div>
  </article>
</main>

<script>
// 이미지 모달 스크립트
let modalIndex = 1;
const modal = document.getElementById('imageModal');
const slides = document.getElementsByClassName('modal-slides');

function openModal() {
  modal.style.display = "block";
  document.body.style.overflow = "hidden"; // 배경 스크롤 방지
  showSlides(modalIndex);
}

function closeModal() {
  modal.style.display = "none";
  document.body.style.overflow = ""; // 스크롤 복원
}

function plusSlides(n) {
  showSlides(modalIndex += n);
}

function currentSlide(n) {
  showSlides(modalIndex = n);
}

function showSlides(n) {
  if (n > slides.length) {modalIndex = 1}
  if (n < 1) {modalIndex = slides.length}
  
  for (let i = 0; i < slides.length; i++) {
    slides[i].style.display = "none";
  }
  
  slides[modalIndex-1].style.display = "block";
}

// ESC 키로 모달 닫기
window.addEventListener('keydown', function(event) {
  if (event.key === 'Escape' && modal.style.display === 'block') {
    closeModal();
  }
});

// 좌우 화살표 키로 이미지 이동
window.addEventListener('keydown', function(event) {
  if (modal.style.display === 'block') {
    if (event.key === 'ArrowLeft') {
      plusSlides(-1);
    } else if (event.key === 'ArrowRight') {
      plusSlides(1);
    }
  }
});

// 모달 외부 클릭 시 닫기
modal.addEventListener('click', function(event) {
  if (event.target === modal) {
    closeModal();
  }
});
</script>

<?php include 'footer.php'; ?>