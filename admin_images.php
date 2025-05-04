<?php
session_start();
require_once 'config.php';
require_once 'header.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo "<main class='login-wrap'><div class='login-box'><p>⚠️ 관리자 권한이 없습니다.</p></div></main>";
    require_once 'footer.php';
    exit;
}

// 업로드 처리
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["image"])) {
    $title = trim($_POST["title"] ?? '');
    $is_featured = isset($_POST["is_featured"]) ? 1 : 0;
    $filename = basename($_FILES["image"]["name"]);
    $target_dir = "img/";
    $target_file = $target_dir . $filename;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        // 기존 대표 이미지 해제
        if ($is_featured) {
            $conn->query("UPDATE images SET is_featured = 0");
        }

        $stmt = $conn->prepare("INSERT INTO images (title, filename, is_featured) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $filename, $is_featured);
        $stmt->execute();

        $success = true;
    } else {
        $error = "파일 업로드 실패. 권한이나 파일 크기를 확인하세요.";
    }
}

// 이미지 목록 조회
$images = $conn->query("SELECT * FROM images ORDER BY uploaded_at DESC");
?>

<main class="join-wrap">
  <div class="join-box" style="width:100%; max-width:860px;">
    <h2>🖼 이미지 업로드</h2>

    <?php if (!empty($success)): ?>
      <p class="form-success">✅ 업로드가 완료되었습니다.</p>
    <?php elseif (!empty($error)): ?>
      <p class="form-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <input type="text" name="title" class="form-input" placeholder="이미지 제목" required>
      <input type="file" name="image" class="form-input" accept="image/*" required>
      <label style="font-size: .95rem;">
        <input type="checkbox" name="is_featured"> 대표 이미지로 설정
      </label>
      <button type="submit" class="btn-join">업로드</button>
    </form>

    <hr style="margin:2.5rem 0">

    <h3 style="font-size:1.3rem; margin-bottom:1.2rem;">📂 업로드된 이미지 목록</h3>

    <div class="grid grid--3">
      <?php while ($img = $images->fetch_assoc()): ?>
        <div class="card">
          <img src="img/<?= htmlspecialchars($img['filename']) ?>" alt="<?= htmlspecialchars($img['title']) ?>">
          <div class="card-body">
            <h3><?= htmlspecialchars($img['title']) ?></h3>
            <p style="font-size:.85rem;">
              <?= $img['is_featured'] ? '<strong style="color:var(--blue)">🌟 대표 이미지</strong><br>' : '' ?>
              <?= date("Y-m-d", strtotime($img['uploaded_at'])) ?>
            </p>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</main>

<?php require_once 'footer.php'; ?>
