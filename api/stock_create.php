<?php
// api/stock_create.php
require 'config.php';         // ใน config.php ควรมี session_start() และฟังก์ชัน db()
header('Content-Type: application/json');

// 1. ตรวจสอบสิทธิ์เฉพาะ admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  http_response_code(403);
  exit(json_encode(['success' => false, 'message' => 'Access denied']));
}

// 2. รับค่าจาก POST
$name     = trim($_POST['name']    ?? '');
$price    = floatval($_POST['price']   ?? 0);
$color    = trim($_POST['color']   ?? '');
$quantity = intval($_POST['quantity'] ?? 0);

// 3. ตรวจสอบความถูกต้องของข้อมูลเบื้องต้น
if ($name === '' || $price <= 0 || $quantity < 0) {
  http_response_code(400);
  exit(json_encode(['success' => false, 'message' => 'Invalid input']));
}

// 4. จัดการอัปโหลดรูปภาพ (ถ้ามี)
$imagePath = null;
if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
  $uploadDir = __DIR__ . '/../uploads';
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }
  $ext    = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
  $newName = uniqid('img_') . '.' . $ext;
  $dest   = "$uploadDir/$newName";
  if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
    // เก็บเป็น path ที่เรียกได้จาก frontend
    $imagePath = "/uploads/$newName";
  }
}

try {
  // 5. บันทึกลงฐานข้อมูล
  $pdo = db();
  $stmt = $pdo->prepare("
    INSERT INTO stock
      (name, price, color, image, quantity, created_at, updated_at)
    VALUES
      (?, ?, ?, ?, ?, NOW(), NOW())
  ");
  $stmt->execute([
    $name,
    $price,
    $color,
    $imagePath,
    $quantity
  ]);
  $newId = $pdo->lastInsertId();

  // 6. บันทึก log การสร้างสินค้า
  $userId = $_SESSION['user']['id'];
  $ip     = $_SERVER['REMOTE_ADDR'];
  $ua     = $_SERVER['HTTP_USER_AGENT'] ?? '';
  $pdo->prepare("
    INSERT INTO user_logs (user_id, action, ip_address, user_agent)
    VALUES (?, 'เพิ่มรายการสินค้า', ?, ?)
  ")->execute([$userId, $ip, $ua]);

  // 7. ตอบกลับ success พร้อม id ใหม่
  echo json_encode(['success' => true, 'id' => $newId]);

} catch (\Exception $e) {
  // 8. กรณีเกิดข้อผิดพลาด
  http_response_code(500);
  error_log("stock_create error: " . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Cannot create record']);
}
