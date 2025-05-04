<?php require_once 'functions.php'; ?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $page_title ?? 'LIBERTINUS ORCHESTRA' ?></title>

  <!-- 파비콘 -->
  <link rel="icon" type="image/png" href="../img/logo.png" />
  <link rel="stylesheet" href="style.css" />
  
  <style>
    /* 프로필 메뉴 스타일 */
    .header-inner {
      position: relative;
      display: flex;
      align-items: center;
    }
    
    /* 메인 내비게이션 정렬 */
    #mainNav {
      margin-left: auto;
      margin-right: 30px; /* 프로필 이미지와의 간격 증가 */
    }
    
    /* 메인 메뉴 아이템 정렬 */
    #mainNav ul {
      justify-content: flex-end;
    }
    
    /* 프로필 메뉴 컨테이너 */
    .profile-menu-container {
      position: relative;
    }
    
    .profile-image-header {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      cursor: pointer;
      border: 2px solid #f0f0f0;
      transition: border-color 0.2s;
    }
    
    .profile-image-header:hover {
      border-color: var(--blue);
    }
    
    .profile-dropdown {
      position: absolute;
      top: 100%;
      right: 0;
      width: 220px;
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.15);
      padding: 1rem;
      z-index: 1000;
      display: none;
      text-align: center;
      margin-top: 0.5rem;
    }
    
    .profile-dropdown.show {
      display: block;
      animation: fadeIn 0.2s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .dropdown-profile-image {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      margin: 0 auto 0.5rem;
      border: 3px solid #f0f0f0;
    }
    
    .dropdown-greeting {
      font-size: 0.95rem;
      color: #333;
      margin-bottom: 1rem;
      font-weight: 500;
    }
    
    .dropdown-menu-items {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
    
    .dropdown-menu-item {
      display: block;
      padding: 0.6rem;
      text-decoration: none;
      color: #333;
      border-radius: 5px;
      transition: background-color 0.2s, color 0.2s;
      font-size: 0.9rem;
    }
    
    .dropdown-menu-item:hover {
      background-color: #f1f5ff;
      color: var(--blue);
    }
    
    .dropdown-menu-item.logout {
      color: #e74c3c;
    }
    
    .dropdown-menu-item.logout:hover {
      background-color: #fdf0ef;
    }
    
    /* 모바일에서는 프로필 메뉴를 숨김 */
    @media (max-width: 1000px) {
      .profile-menu-container {
        display: none;
      }
      
      #mainNav ul {
        justify-content: flex-start;
      }
      
      #hamburger {
        margin-left: auto;
      }
    }
    
    /* 모바일 메뉴에서만 보이는 항목 스타일 */
    @media (min-width: 1001px) {
      .mobile-only {
        display: none !important;
      }
      
      #hamburger {
        display: none;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="container header-inner">
      <!-- 로고 -->
      <a class="logo" href="index.php">
        <img src="../img/logo.png" alt="로고" class="logo-img" />
        <span class="logo-text">&nbsp;LIBERTINUS ORCHESTRA</span>
      </a>

      <!-- 내비 -->
      <nav id="mainNav">
        <ul>
          <li><a href="about.php">소개</a></li>
          <li><a href="members.php">단원</a></li>
          <li><a href="performances.php">공연</a></li>
          <li><a href="news.php">소식</a></li>
          <li><a href="contact.php">입단지원/문의</a></li>
          <?php if (!is_logged_in()): ?>
            <li><a href="login.php">로그인</a></li>
          <?php else: ?>
            <!-- 모바일 햄버거 메뉴에서만 보이는 링크 -->
            <?php if (is_admin()): ?>
              <li class="mobile-only"><a href="admin.php">관리자 페이지</a></li>
            <?php else: ?>
              <li class="mobile-only"><a href="mypage.php">회원정보 수정</a></li>
            <?php endif; ?>
            <li class="mobile-only"><a href="logout.php">로그아웃</a></li>
          <?php endif; ?>
        </ul>
      </nav>
      
      <!-- 햄버거 (모바일) -->
      <button id="hamburger" aria-label="Open Menu">
        <span></span><span></span><span></span>
      </button>
      
      <?php if (is_logged_in()): ?>
      <div class="profile-menu-container">
        <?php
          // 기본 이미지 경로 설정
          $profile_img_dir = "../img/profile/";
          $default_img = $profile_img_dir . "profile_6800f3c5a647f.png";
          
          // 세션에서 사용자 ID 가져오기
          $user_id = $_SESSION['user_id'];
          $user_name = '';
          $profile_img = $default_img;
          
          // DB에서 사용자 정보 가져오기 - 오류 예방을 위한 안전한 방식
          try {
            if (!isset($pdo) && file_exists('config.php')) {
              require_once 'config.php';
            }
            
            if (isset($pdo)) {
              $stmt = $pdo->prepare("SELECT name, profile_img FROM users WHERE id = ?");
              $stmt->execute([$user_id]);
              $user_data = $stmt->fetch();
              
              if ($user_data) {
                $user_name = $user_data['name'];
                if (!empty($user_data['profile_img'])) {
                  $profile_img = $profile_img_dir . $user_data['profile_img'];
                }
              }
            }
          } catch (Exception $e) {
            // 오류 발생 시 기본값 사용
            error_log('프로필 정보 로딩 오류: ' . $e->getMessage());
          }
          
          // 이름 기본값
          if (empty($user_name)) {
            $user_name = '회원';
          }
        ?>
        <img src="<?= htmlspecialchars($profile_img) ?>" alt="프로필" class="profile-image-header" id="profileMenuBtn">
        <div class="profile-dropdown" id="profileDropdown">
          <img src="<?= htmlspecialchars($profile_img) ?>" alt="프로필" class="dropdown-profile-image">
          <div class="dropdown-greeting">안녕하세요, <?= htmlspecialchars($user_name) ?>님</div>
          <div class="dropdown-menu-items">
            <?php if (is_admin()): ?>
              <a href="admin.php" class="dropdown-menu-item">관리자 페이지</a>
            <?php else: ?>
              <a href="mypage.php" class="dropdown-menu-item">회원정보 수정</a>
            <?php endif; ?>
            <a href="logout.php" class="dropdown-menu-item logout">로그아웃</a>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </header>

  <script>
    /* 햄버거 토글 */
    const ham=document.getElementById('hamburger');
    const nav=document.getElementById('mainNav');
    ham.onclick=()=>{ ham.classList.toggle('open'); nav.classList.toggle('open'); }
    
    /* 프로필 메뉴 토글 */
    const profileMenuBtn = document.getElementById('profileMenuBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    
    if (profileMenuBtn) {
      profileMenuBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        profileDropdown.classList.toggle('show');
      });
      
      // 다른 곳을 클릭하면 드롭다운 닫기
      document.addEventListener('click', function(e) {
        if (profileDropdown.classList.contains('show') && !profileDropdown.contains(e.target)) {
          profileDropdown.classList.remove('show');
        }
      });
    }
  </script>