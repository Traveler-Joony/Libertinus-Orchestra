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
  
  <!-- Font Awesome 추가 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
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
    
    /* 알림 아이콘 */
    .notification-icon {
      position: absolute;
      top: -5px;
      right: -5px;
      background-color: #e74c3c;
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      font-size: 0.75rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }
    
    .profile-dropdown {
      position: absolute;
      top: 100%;
      right: 0;
      width: 320px;
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
    
    /* 알림 관련 추가 스타일 */
    .notifications-tab {
      display: flex;
      justify-content: center;
      margin-bottom: 1rem;
      border-bottom: 1px solid #eee;
    }
    
    .notifications-tab-item {
      padding: 0.5rem 1rem;
      cursor: pointer;
      font-weight: 500;
      color: #555;
      position: relative;
    }
    
    .notifications-tab-item.active {
      color: var(--blue);
      border-bottom: 2px solid var(--blue);
    }
    
    .notifications-content {
      max-height: 300px;
      overflow-y: auto;
    }
    
    .notification-item {
      padding: 0.75rem;
      border-bottom: 1px solid #f0f0f0;
      text-align: left;
      cursor: pointer;
      transition: background-color 0.2s;
      position: relative;
    }
    
    .notification-item:hover {
      background-color: #f8f9fa;
    }
    
    .notification-item.unread {
      background-color: #f0f7ff;
    }
    
    .notification-item.unread:hover {
      background-color: #e1f0ff;
    }
    
    .notification-content {
      font-size: 0.9rem;
      color: #333;
      margin-bottom: 0.25rem;
    }
    
    .notification-time {
      font-size: 0.8rem;
      color: #999;
    }
    
    .notification-mark-read {
      position: absolute;
      top: 0.5rem;
      right: 0.5rem;
      width: 20px;
      height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.8rem;
      color: #aaa;
      cursor: pointer;
      transition: color 0.2s;
      visibility: hidden;
    }
    
    .notification-item:hover .notification-mark-read {
      visibility: visible;
    }
    
    .notification-mark-read:hover {
      color: var(--blue);
    }
    
    .no-notifications {
      padding: 2rem 0;
      text-align: center;
      color: #999;
      font-size: 0.9rem;
    }
    
    .show-all-notifications {
      display: block;
      text-align: center;
      padding: 0.75rem;
      color: var(--blue);
      font-size: 0.9rem;
      text-decoration: none;
      border-top: 1px solid #f0f0f0;
      margin-top: 0.5rem;
    }
    
    .show-all-notifications:hover {
      background-color: #f8f9fa;
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
          <li><a href="community.php">커뮤니티</a></li>
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
              
              // 안 읽은 알림 개수 가져오기
              $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
              $stmt->execute([$user_id]);
              $unread_notifications_count = $stmt->fetchColumn();
            }
          } catch (Exception $e) {
            // 오류 발생 시 기본값 사용
            error_log('프로필 정보 로딩 오류: ' . $e->getMessage());
            $unread_notifications_count = 0;
          }
          
          // 이름 기본값
          if (empty($user_name)) {
            $user_name = '회원';
          }
        ?>
        <div style="position: relative;">
          <img src="<?= htmlspecialchars($profile_img) ?>" alt="프로필" class="profile-image-header" id="profileMenuBtn">
          <?php if ($unread_notifications_count > 0): ?>
            <div class="notification-icon"><?= min($unread_notifications_count, 9) ?><?= $unread_notifications_count > 9 ? '+' : '' ?></div>
          <?php endif; ?>
        </div>
        <div class="profile-dropdown" id="profileDropdown">
          <img src="<?= htmlspecialchars($profile_img) ?>" alt="프로필" class="dropdown-profile-image">
          <div class="dropdown-greeting">안녕하세요, <?= htmlspecialchars($user_name) ?>님</div>
          
          <!-- 알림 탭 -->
          <div class="notifications-tab">
            <div class="notifications-tab-item active" data-tab="notification">알림</div>
            <div class="notifications-tab-item" data-tab="menu">메뉴</div>
          </div>
          
          <!-- 알림 목록 -->
          <div class="notifications-content" id="notificationTab">
            <?php
            if (isset($pdo)) {
              $stmt = $pdo->prepare("
                SELECT n.*, p.title as post_title, u.name as sender_name 
                FROM notifications n
                JOIN community_posts p ON n.post_id = p.id
                JOIN users u ON n.sender_id = u.id
                WHERE n.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT 10
              ");
              $stmt->execute([$user_id]);
              $notifications = $stmt->fetchAll();
              
              if (count($notifications) > 0):
            ?>
              <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>" 
                     data-id="<?= $notification['id'] ?>" 
                     data-url="community_post.php?id=<?= $notification['post_id'] ?>#comment-<?= $notification['related_id'] ?>">
                  <div class="notification-content">
                    <?= htmlspecialchars($notification['content']) ?>
                  </div>
                  <div class="notification-time">
                    <?= get_time_ago($notification['created_at']) ?>
                  </div>
                  <div class="notification-mark-read" title="읽음 표시">
                    <i class="fas fa-check"></i>
                  </div>
                </div>
              <?php endforeach; ?>
              <a href="notifications.php" class="show-all-notifications">모든 알림 보기</a>
            <?php else: ?>
              <div class="no-notifications">
                새로운 알림이 없습니다.
              </div>
            <?php 
              endif;
            } else {
            ?>
              <div class="no-notifications">
                알림을 불러올 수 없습니다.
              </div>
            <?php
            }
            ?>
          </div>
          
          <!-- 메뉴 목록 -->
          <div class="notifications-content" id="menuTab" style="display: none;">
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
      
      // 알림 탭 전환
      const tabItems = document.querySelectorAll('.notifications-tab-item');
      const notificationTab = document.getElementById('notificationTab');
      const menuTab = document.getElementById('menuTab');
      
      tabItems.forEach(item => {
        item.addEventListener('click', function() {
          // 모든 탭 비활성화
          tabItems.forEach(tab => tab.classList.remove('active'));
          
          // 현재 탭 활성화
          this.classList.add('active');
          
          // 탭 내용 전환
          if (this.dataset.tab === 'notification') {
            notificationTab.style.display = 'block';
            menuTab.style.display = 'none';
          } else {
            notificationTab.style.display = 'none';
            menuTab.style.display = 'block';
          }
        });
      });
      
      // 알림 클릭 시 해당 페이지로 이동
      const notificationItems = document.querySelectorAll('.notification-item');
      notificationItems.forEach(item => {
        item.addEventListener('click', function(e) {
          // 읽음 표시 버튼 클릭 시 이벤트 전파 방지
          if (e.target.closest('.notification-mark-read')) {
            e.stopPropagation();
            return;
          }
          
          const url = this.dataset.url;
          if (url) {
            // 알림 읽음 처리
            markNotificationAsRead(this.dataset.id);
            // 페이지 이동
            window.location.href = url;
          }
        });
      });
      
      // 알림 읽음 표시 버튼
      const markReadButtons = document.querySelectorAll('.notification-mark-read');
      markReadButtons.forEach(button => {
        button.addEventListener('click', function(e) {
          e.stopPropagation();
          const item = this.closest('.notification-item');
          markNotificationAsRead(item.dataset.id);
          item.classList.remove('unread');
        });
      });
      
      // 알림 읽음 처리 함수
      function markNotificationAsRead(id) {
        fetch('notification_process.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `action=mark_read&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // 알림 아이콘 카운트 업데이트
            updateNotificationCount();
          }
        })
        .catch(error => console.error('Error:', error));
      }
      
      // 알림 카운트 업데이트 함수
      function updateNotificationCount() {
        fetch('notification_process.php?action=count')
        .then(response => response.json())
        .then(data => {
          const notificationIcon = document.querySelector('.notification-icon');
          if (data.count > 0) {
            if (notificationIcon) {
              notificationIcon.textContent = data.count > 9 ? '9+' : data.count;
            } else {
              const profileContainer = document.querySelector('.profile-menu-container > div');
              const newIcon = document.createElement('div');
              newIcon.className = 'notification-icon';
              newIcon.textContent = data.count > 9 ? '9+' : data.count;
              profileContainer.appendChild(newIcon);
            }
          } else if (notificationIcon) {
            notificationIcon.remove();
          }
        })
        .catch(error => console.error('Error:', error));
      }
    }
  </script>