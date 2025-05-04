<?php
/* /www/test/index.php */
$page_title = 'LIBERTINUS ORCHESTRA';
include 'header.php';
require_once 'config.php';

// 메인 페이지에 표시할 공연 정보 가져오기
$stmt = $pdo->query("SELECT * FROM performances WHERE main_display = 1 ORDER BY created_at DESC LIMIT 3");
$performances = $stmt->fetchAll();

// 메인 페이지에 표시할 공지사항 가져오기
$stmt = $pdo->query("SELECT * FROM news ORDER BY created_at DESC LIMIT 3");
$news = $stmt->fetchAll();

// 현재 회장의 전화번호 가져오기
$president_query = "SELECT mobile FROM users WHERE position = '회장' AND graduate = 0 LIMIT 1";
$president_stmt = $pdo->query($president_query);
$president_mobile = $president_stmt->fetchColumn();

// 회장 전화번호가 없는 경우 기본 전화번호 사용
if (!$president_mobile) {
    $president_mobile = "010-4308-8948";
}
?>

<style>
/* 카드 스타일 수정 */
.card {
  text-decoration: none !important;
  text-align: center;
  transition: transform 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
}

.card-body h3 {
  color: #333;
  margin-bottom: 0.5rem;
}

.card-meta {
  color: #666;
  font-size: 0.9rem;
}

/* 섹션 구분선 및 간격 */
section {
  padding: 5rem 0;
}

section:not(:last-child) {
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

/* 연락처 및 SNS 섹션 */
.contact-section {
  background-color: #f9f9f9;
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
}

.social-links a {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background-color: #3498db;
  color: white;
  border-radius: 50%;
  text-decoration: none;
  transition: background-color 0.3s;
}

.social-links a:hover {
  background-color: #2980b9;
}

.cta-buttons {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  justify-content: center;
  height: 100%;
}

.cta-button {
  display: inline-block;
  padding: 1rem 2rem;
  background-color: #3498db;
  color: white;
  text-decoration: none;
  border-radius: 4px;
  font-weight: 600;
  text-align: center;
  transition: background-color 0.3s, transform 0.3s;
}

.cta-button:hover {
  background-color: #2980b9;
  transform: translateY(-2px);
}

.cta-button.secondary {
  background-color: #2ecc71;
}

.cta-button.secondary:hover {
  background-color: #27ae60;
}
</style>

<section class="hero hero--visual" id="top">
  <div class="hero-slider">
    <?php
    // 이미지 디렉토리 경로
    $imageDir = './img/main/';
    $images = [];
    
    // 디렉토리 내 모든 이미지 파일 가져오기
    if (is_dir($imageDir)) {
        if ($dh = opendir($imageDir)) {
            while (($file = readdir($dh)) !== false) {
                // 이미지 파일 확장자만 필터링 (.jpg, .jpeg, .png, .gif)
                if (preg_match('/(\.jpg|\.jpeg|\.png|\.gif)$/i', $file)) {
                    $images[] = $imageDir . $file;
                }
            }
            closedir($dh);
        }
    }
    
    // 이미지 없을 경우 기본 이미지 제공
    if (empty($images)) {
        $images[] = './img/thumb.jpg';
    }
    
    // 첫 번째 슬라이드는 active 클래스로 표시
    foreach ($images as $index => $image) {
        $activeClass = ($index === 0) ? 'active' : '';
        echo "<div class='hero-slide $activeClass' style='background-image: url(\"$image\");'></div>";
    }
    ?>
  </div>
  
  <div class="container hero-content">
    <h1 style="margin-bottom:1.25rem;">음악의 감동으로<br class="m-only">하나 되는 순간</h1>
    <p>LIBERTINUS ORCHESTRA</p>
  </div>

  <!-- ↓ 스크롤 유도 아이콘 -->
  <a href="#about" class="scroll-down">⌄</a>
</section>

<!-- ───────── About + CTA 카드 ───────── -->
<section id="about">
  <div class="container grid grid--2 about-split">
    <div>
      <h2 class="section-title" style="text-align:left;">ABOUT US</h2>
      <p style="margin-bottom:1.5rem;">
        LIBERTINUS ORCHESTRA는 <strong>1978년 창단</strong> 이래 '음악을 사랑하는, 음악을 나누는' 
        순천향대학교의 유일한 순수음악 동아리로 성장해왔습니다. 
        클래식에 기반을 두고 OST, 재즈 등 다양한 장르를 아우르는 레퍼토리로 
        관객과 소통하는 무대를 만들어가고 있습니다. 
        정기연주회, 앙상블 공연, 홈커밍 데이 등 다양한 활동을 통해 
        "음악을 통한 소통과 성장"이라는 가치를 실천합니다.
      </p>
      <a href="about.php" class="btn">더 알아보기</a>
    </div>

    <div class="about-thumb">
      <img src="../img/thumb.jpg" alt="오케스트라 연주 모습" style="max-width: 100%; width: 500px; height: auto;">
    </div>
  </div>
</section>

<section>
  <div class="container">
    <h2 class="section-title">소식</h2>
    <div class="grid grid--3">
      <?php
        $news_query = "SELECT id, title, created_at, featured_image FROM news WHERE display = 1 AND main_display = 1 ORDER BY created_at DESC LIMIT 4";
        $news_stmt = $pdo->query($news_query);
        $news_items = $news_stmt->fetchAll();
        
        foreach($news_items as $news) {
          echo "<a href='news_detail.php?id={$news['id']}' class='card'>";
          if (!empty($news['featured_image'])) {
            echo "<img src='../img/news/{$news['featured_image']}' alt='소식 이미지'>";
          } else {
            echo "<img src='../img/logo.png' alt='기본 이미지'>";
          }
          echo "<div class='card-body'>";
          echo "<h3>" . htmlspecialchars($news['title']) . "</h3>";
          echo "<p class='card-meta'>" . date("Y-m-d", strtotime($news['created_at'])) . "</p>";
          echo "</div></a>";
        }
      ?>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <h2 class="section-title">임원진 소개</h2>
    <div class="grid grid--3">
      <?php
        $members_query = "SELECT id, name, position, dept, profile_img FROM users WHERE position IN ('지휘자', '회장', '부회장', '총무') AND graduate = 0 ORDER BY FIELD(position, '지휘자', '회장', '부회장', '총무')";
        $members_stmt = $pdo->query($members_query);
        $members = $members_stmt->fetchAll();
        
        foreach($members as $member) {
          echo "<a href='members.php' class='card'>";
          if (!empty($member['profile_img'])) {
            echo "<img src='../img/profile/{$member['profile_img']}' alt='임원진 프로필'>";
          } else {
            echo "<img src='../img/profile/logo.jpg' alt='기본 프로필'>";
          }
          echo "<div class='card-body'>";
          echo "<h3>" . htmlspecialchars($member['name']) . " (" . htmlspecialchars($member['position']) . ")</h3>";
          echo "<p class='card-meta'>" . htmlspecialchars($member['dept']) . "</p>";
          echo "</div></a>";
        }
      ?>
    </div>
  </div>
</section>

<!-- 연락처 및 지원 버튼 섹션 -->
<section class="contact-section">
  <div class="container grid grid--2">
    <div>
      <h2 class="section-title" style="text-align:left;">연락처 및 SNS</h2>
      
      <div class="contact-info">
        <p><strong>주소:</strong> 충청남도 아산시 순천향로 22 학생회관 S301(신창면, 순천향대학교)</p>
        <p><strong>연락처:</strong> <?php echo htmlspecialchars($president_mobile); ?></p>
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
    
    <div class="cta-buttons">
      <a href="contact.php" class="cta-button">신규단원 지원하기</a>
      <a href="register.php" class="cta-button secondary">회원가입</a>
    </div>
  </div>
</section>

<?php include 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const slides = document.querySelectorAll('.hero-slide');
  let currentIndex = 0;
  
  // 슬라이드가 없으면 종료
  if (slides.length === 0) return;
  
  // 슬라이드 전환 함수
  function nextSlide() {
    slides[currentIndex].classList.remove('active');
    currentIndex = (currentIndex + 1) % slides.length;
    slides[currentIndex].classList.add('active');
  }
  
  // 5초마다 슬라이드 전환
  setInterval(nextSlide, 5000);
  
  // 스크롤 다운 버튼 처리
  const scrollDownBtn = document.querySelector('.scroll-down');
  if (scrollDownBtn) {
    scrollDownBtn.addEventListener('click', function(e) {
      e.preventDefault();
      const targetSection = document.querySelector(this.getAttribute('href'));
      if (targetSection) {
        targetSection.scrollIntoView({ behavior: 'smooth' });
      }
    });
  }
});
</script>