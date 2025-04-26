<?php
// api/stock_update.php
require 'config.php';
header('Content-Type: application/json');
session_start();

// 1) ตรวจสอบสิทธิ์เฉพาะ admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  http_response_code(403);
  exit(json_encode([
    'success' => false,
    'message' => 'Access denied'
  ]));
}

$uid      = $_SESSION['user']['id'];
$id       = $_POST['id']       ?? null;
$name     = trim($_POST['name']     ?? '');
$price    = floatval($_POST['price']    ?? 0);
$color    = $_POST['color']    ?? null;
$quantity = intval($_POST['quantity'] ?? 0);

if (!$id) {
  http_response_code(400);
  exit(json_encode([
    'success' => false,
    'message' => 'Missing stock ID'
  ]));
}

$pdo = db();

// 2) โหลดไฟล์ภาพเดิม (กรณีไม่อัปเดตภาพ)
$oldStmt = $pdo->prepare("SELECT image FROM stock WHERE id = ?");
$oldStmt->execute([$id]);
$oldRec = $oldStmt->fetch(PDO::FETCH_ASSOC);
$imagePath = $oldRec['image'] ?? null;

// 3) ถ้ามีไฟล์ใหม่ ให้ upload แล้วแทนที่
if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
  $uploadDir = __DIR__ . '/../uploads';
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }

  $ext     = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
  $newName = uniqid('img_') . '.' . $ext;
  $dest    = $uploadDir . '/' . $newName;

  if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
    $imagePath = '/uploads/' . $newName;
    // (ถ้าต้องการ) ลบไฟล์เก่าออก:
    // if ($oldRec['image']) unlink(__DIR__ . '/..' . $oldRec['image']);
  }
}

try {
  // 4) อัปเดตข้อมูลในตาราง stock
  $update = $pdo->prepare("
    UPDATE stock
    SET
      name       = ?,
      price      = ?,
      color      = ?,
      image      = ?,
      quantity   = ?,
      updated_at = NOW()
    WHERE id = ?
  ");
  $update->execute([
    $name,
    $price,
    $color,
    $imagePath,
    $quantity,
    $id
  ]);

  // 5) ตอบกลับ success
  echo json_encode(['success' => true]);

  // 6) บันทึก log การอัปเดต
  $ip = $_SERVER['REMOTE_ADDR'] ?? '';
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  $log = $pdo->prepare("
    INSERT INTO user_logs (user_id, action, ip_address, user_agent)
    VALUES (?, 'แก้ไขสินค้า', ?, ?)
  ");
  $log->execute([$uid, $ip, $ua]);

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'Cannot update record'
  ]);
  // (ถ้าต้องการ) เขียน $e->getMessage() ลงไฟล์ log ของเซิร์ฟเวอร์
}
