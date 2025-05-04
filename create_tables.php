<?php
require_once 'config.php';

// 기존 테이블 생성 코드 유지...

// 이미지 테이블 생성
$sql = "CREATE TABLE IF NOT EXISTS images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "images 테이블이 준비되었습니다.<br>";
} else {
    echo "Error creating images table: " . $conn->error . "<br>";
}

// 다른 테이블 생성 코드도 여기에 있을 수 있음...

$conn->close();
?>
