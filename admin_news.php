<?php
session_start();
require_once 'config.php';
require_once 'header.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo "<main class='login-wrap'><div class='login-box'><p>⚠️ 관리자 권한이 없습니다.</p></div></main>";
    require_once 'footer.php';
    exit;
}

$success = false;
$error = '';
$edit_mode = false;
$edit_id = $_GET['edit'] ?? null;

// 삭제 요청 처리
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // 이미지 파일 삭제를 위해 먼저 이미지 정보 가져오기
    $img_stmt = $conn->prepare("SELECT featured_image FROM news WHERE id = ?");
    $img_stmt->bind_param("i", $id);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();
    $img_data = $img_result->fetch_assoc();
    
    // 추가 이미지들 정보 가져오기
    $gallery_stmt = $conn->prepare("SELECT image_path FROM news_images WHERE news_id = ?");
    $gallery_stmt->bind_param("i", $id);
    $gallery_stmt->execute();
    $gallery_result = $gallery_stmt->get_result();
    $gallery_images = [];
    while ($row = $gallery_result->fetch_assoc()) {
        $gallery_images[] = $row['image_path'];
    }
    
    // 뉴스 및 연결된 이미지 레코드 삭제 (외래키 제약조건으로 자동 삭제됨)
    $conn->query("DELETE FROM news WHERE id = $id");
    
    // 물리적 이미지 파일 삭제
    if (!empty($img_data['featured_image'])) {
        $featured_path = "./img/news/" . $img_data['featured_image'];
        if (file_exists($featured_path)) {
            @unlink($featured_path);
        }
    }
    
    // 갤러리 이미지 파일들 삭제
    foreach ($gallery_images as $image) {
        $gallery_path = "./img/news/" . $image;
        if (file_exists($gallery_path)) {
            @unlink($gallery_path);
        }
    }
    
    header("Location: admin_news.php");
    exit;
}

// 이미지 단일 삭제 처리
if (isset($_GET['delete_image'])) {
    $image_id = intval($_GET['delete_image']);
    $news_id = intval($_GET['news_id']);
    
    // 이미지 파일 경로 가져오기
    $img_stmt = $conn->prepare("SELECT image_path FROM news_images WHERE id = ?");
    $img_stmt->bind_param("i", $image_id);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();
    $img_data = $img_result->fetch_assoc();
    
    // 이미지 레코드 삭제
    $del_stmt = $conn->prepare("DELETE FROM news_images WHERE id = ?");
    $del_stmt->bind_param("i", $image_id);
    $del_stmt->execute();
    
    // 물리적 이미지 파일 삭제
    if (!empty($img_data['image_path'])) {
        $image_path = "./img/news/" . $img_data['image_path'];
        if (file_exists($image_path)) {
            @unlink($image_path);
        }
    }
    
    header("Location: admin_news.php?edit=" . $news_id);
    exit;
}

// 수정모드 데이터 불러오기
$edit_data = [
    'title' => '',
    'content' => '',
    'featured_image' => '',
    'youtube_url' => '',
    'main_display' => 0, // 기본값: 공지 아님
    'display' => 1       // 기본값: 보이기
];

$gallery_images = [];

if ($edit_id) {
    $edit_mode = true;
    
    // 뉴스 기본 정보 가져오기
    $stmt = $conn->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();
    
    // 뉴스 추가 이미지 가져오기
    $img_stmt = $conn->prepare("SELECT * FROM news_images WHERE news_id = ? ORDER BY sort_order ASC");
    $img_stmt->bind_param("i", $edit_id);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();
    $gallery_images = $img_result->fetch_all(MYSQLI_ASSOC);
}

// 글 저장 처리 (등록 또는 수정)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $youtube_url = trim($_POST['youtube_url'] ?? '');
    $main_display = isset($_POST['main_display']) ? 1 : 0; // 체크박스 값
    $display = isset($_POST['display']) ? 1 : 0; // 체크박스 값
    $filename = $_POST['existing_image'] ?? null;

    // 대표 이미지 업로드
    if (!empty($_FILES["featured_image"]["name"])) {
        $target_dir = "./img/news/"; // 이미지 저장 경로
        $filename = basename($_FILES["featured_image"]["name"]);
        // 중복 방지를 위한 파일명 수정
        $filename = time() . '_' . $filename;
        $target_file = $target_dir . $filename;

        // 디렉토리가 없으면 생성
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        if (!move_uploaded_file($_FILES["featured_image"]["tmp_name"], $target_file)) {
            $error = "대표 이미지 업로드 실패";
        }
    }
    
    if (!$error) {
        $conn->begin_transaction(); // 트랜잭션 시작
        
        try {
            if (!empty($_POST['edit_id'])) {
                // 수정
                $id = intval($_POST['edit_id']);
                $stmt = $conn->prepare("UPDATE news SET title=?, content=?, featured_image=?, youtube_url=?, main_display=?, display=? WHERE id=?");
                $stmt->bind_param("ssssiis", $title, $content, $filename, $youtube_url, $main_display, $display, $id);
                $stmt->execute();
                $news_id = $id;
            } else {
                // 새 글 등록
                $stmt = $conn->prepare("INSERT INTO news (title, content, featured_image, youtube_url, main_display, display, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssssii", $title, $content, $filename, $youtube_url, $main_display, $display);
                $stmt->execute();
                $news_id = $conn->insert_id;
            }
            
            // 갤러리 이미지 업로드 처리
            if (!empty($_FILES['gallery_images']['name'][0])) {
                $gallery_dir = "./img/news/"; // 갤러리 이미지 경로
                
                // 디렉토리가 없으면 생성
                if (!file_exists($gallery_dir)) {
                    mkdir($gallery_dir, 0755, true);
                }
                
                // 이미지 순서 결정을 위해 기존 최대 순서 값 가져오기
                $order_stmt = $conn->prepare("SELECT MAX(sort_order) as max_order FROM news_images WHERE news_id = ?");
                $order_stmt->bind_param("i", $news_id);
                $order_stmt->execute();
                $order_result = $order_stmt->get_result();
                $order_data = $order_result->fetch_assoc();
                $max_order = $order_data['max_order'] ?? 0;
                
                // 각 이미지 처리
                $file_count = count($_FILES['gallery_images']['name']);
                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $temp_file = $_FILES['gallery_images']['tmp_name'][$i];
                        $original_filename = $_FILES['gallery_images']['name'][$i];
                        // 중복 방지를 위한 파일명 수정
                        $gallery_filename = time() . '_' . $i . '_' . $original_filename;
                        $gallery_path = $gallery_dir . $gallery_filename;
                        
                        if (move_uploaded_file($temp_file, $gallery_path)) {
                            // 이미지 정보 DB에 저장
                            $sort_order = $max_order + $i + 1;
                            $img_stmt = $conn->prepare("INSERT INTO news_images (news_id, image_path, sort_order, created_at) VALUES (?, ?, ?, NOW())");
                            $img_stmt->bind_param("isi", $news_id, $gallery_filename, $sort_order);
                            $img_stmt->execute();
                        }
                    }
                }
            }
            
            $conn->commit(); // 트랜잭션 커밋
            $success = true;
            header("Location: admin_news.php?edit=" . $news_id);
            exit;
            
        } catch (Exception $e) {
            $conn->rollback(); // 오류 발생 시 롤백
            $error = "DB 오류: " . $e->getMessage();
        }
    }
}

// 뉴스 목록 조회
$news_list = $conn->query("SELECT * FROM news ORDER BY created_at DESC");
?>

<style>
.admin-flex { display: flex; gap: 2rem; align-items: flex-start; flex-wrap: wrap; }
.admin-left, .admin-right { flex: 1 1 400px; }
.news-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
.news-table th, .news-table td { border: 1px solid #ccc; padding: 0.6rem; text-align: left; }
.news-table th { background: #f1f3f5; }
.btn-sm { font-size: 0.85rem; padding: 0.3rem 0.6rem; margin-right: 4px; }
.checkbox-group { display: flex; gap: 1.5rem; margin: 1rem 0; }
.checkbox-item { display: flex; align-items: center; gap: 0.5rem; }
.checkbox-item input[type="checkbox"] { width: 18px; height: 18px; }
.checkbox-item label { font-size: 0.95rem; cursor: pointer; }
.display-status { display: inline-block; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.75rem; }
.status-on { background-color: #e3f8e3; color: #2e8b2e; }
.status-off { background-color: #f8e3e3; color: #8b2e2e; }
.notice-badge { background-color: #fef3e6; color: #e67e22; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.75rem; }
.gallery-preview { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; margin: 1rem 0; }
.gallery-item { border-radius: 4px; overflow: hidden; position: relative; height: 120px; border: 1px solid #ddd; }
.gallery-item img { width: 100%; height: 100%; object-fit: cover; }
.delete-image { position: absolute; top: 5px; right: 5px; background: rgba(255,255,255,0.8); color: #e74c3c; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-weight: bold; text-decoration: none; }
.delete-image:hover { background: rgba(255,0,0,0.1); }
.gallery-label { display: block; margin: 1rem 0 0.5rem; font-size: 0.95rem; }
.featured-image-preview { width: 100%; max-width: 250px; height: auto; margin-top: 10px; border-radius: 4px; }
</style>

<main class="join-wrap" style="padding-top: 150px !important;">

  <div class="container admin-flex">
    <!-- 작성 영역 -->
    <div class="join-box admin-left">
      <h2><?= $edit_mode ? "✏️ 뉴스 수정" : "📰 뉴스 작성" ?></h2>

      <?php if ($success): ?>
        <p class="form-success">✅ 뉴스가 등록되었습니다.</p>
      <?php elseif ($error): ?>
        <p class="form-error"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <input type="text" name="title" class="form-input" placeholder="제목" required value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>">
        <textarea name="content" class="form-input" rows="8" placeholder="내용을 입력하세요" required><?= htmlspecialchars($edit_data['content'] ?? '') ?></textarea>

        <label style="font-size:.95rem;">대표 이미지 업로드</label>
        <input type="file" name="featured_image" class="form-input" accept="image/*">
        <?php if (!empty($edit_data['featured_image'])): ?>
          <p style="font-size:.85rem;">현재 이미지: <?= htmlspecialchars($edit_data['featured_image']) ?></p>
          <img src="./img/news/<?= htmlspecialchars($edit_data['featured_image']) ?>" 
               alt="대표 이미지" class="featured-image-preview">
        <?php endif; ?>
        <input type="hidden" name="existing_image" value="<?= htmlspecialchars($edit_data['featured_image'] ?? '') ?>">

        <label style="font-size:.95rem; margin-top:1rem;">추가 이미지 업로드 (여러 장 선택 가능)</label>
        <input type="file" name="gallery_images[]" class="form-input" accept="image/*" multiple>
        
        <?php if (!empty($gallery_images)): ?>
          <label class="gallery-label">현재 추가 이미지 (<?= count($gallery_images) ?>장)</label>
          <div class="gallery-preview">
            <?php foreach ($gallery_images as $image): ?>
              <div class="gallery-item">
                <img src="./img/news/<?= htmlspecialchars($image['image_path']) ?>" alt="뉴스 이미지">
                <a href="?delete_image=<?= $image['id'] ?>&news_id=<?= $edit_id ?>" 
                   class="delete-image" 
                   onclick="return confirm('이 이미지를 삭제하시겠습니까?')">×</a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <label style="font-size:.95rem;">유튜브 링크</label>
        <input type="url" name="youtube_url" class="form-input" placeholder="https://www.youtube.com/watch?v=..." value="<?= htmlspecialchars($edit_data['youtube_url'] ?? '') ?>">

        <!-- 노출 설정 체크박스 -->
        <div class="checkbox-group">
          <div class="checkbox-item">
            <input type="checkbox" id="main_display" name="main_display" <?= ($edit_data['main_display'] ?? 0) == 1 ? 'checked' : '' ?>>
            <label for="main_display">공지로 표시</label>
          </div>
          <div class="checkbox-item">
            <input type="checkbox" id="display" name="display" <?= ($edit_data['display'] ?? 1) == 1 ? 'checked' : '' ?>>
            <label for="display">목록에 표시</label>
          </div>
        </div>

        <?php if ($edit_mode): ?>
          <input type="hidden" name="edit_id" value="<?= $edit_id ?>">
        <?php endif; ?>

        <button type="submit" class="btn-join"><?= $edit_mode ? "수정하기" : "등록하기" ?></button>
      </form>
    </div>

    <!-- 목록 영역 -->
    <div class="join-box admin-right">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <h2>📋 뉴스 목록</h2>
        <a href="admin_news.php" class="btn btn-sm" style="font-size:0.88rem;">➕ 새 뉴스 작성</a>
      </div>
      <table class="news-table">
        <thead>
          <tr>
            <th>제목</th>
            <th>날짜</th>
            <th>상태</th>
            <th>관리</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $news_list->fetch_assoc()): ?>
            <tr>
              <td>
                <?= $row['main_display'] == 1 ? '<span class="notice-badge">공지</span> ' : '' ?>
                <?= htmlspecialchars($row['title']) ?>
              </td>
              <td><?= substr($row['created_at'], 0, 10) ?></td>
              <td>
                <?php if(isset($row['display'])): ?>
                  <span class="display-status <?= $row['display'] == 1 ? 'status-on' : 'status-off' ?>">
                    <?= $row['display'] == 1 ? '표시중' : '숨김' ?>
                  </span>
                <?php else: ?>
                  <span class="display-status status-on">표시중</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm">✏️</a>
                <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm" onclick="return confirm('삭제하시겠습니까?')">🗑</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </div>
</main>

<?php require_once 'footer.php'; ?>