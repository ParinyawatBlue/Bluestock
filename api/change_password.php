<?php
// api/change_password.php
require 'config.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Not authenticated']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$old   = trim($input['old'] ?? '');
$new   = trim($input['new'] ?? '');

if ($old === '' || $new === '') {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'กรอกข้อมูลไม่ครบ']);
  exit;
}

$uid = $_SESSION['user']['id'];
$pdo = db();

// ตรวจสอบรหัสเดิม
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$uid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || !password_verify($old, $row['password'])) {
  echo json_encode(['success' => false, 'message' => 'รหัสเดิมไม่ถูกต้อง']);
  exit;
}

// อัปเดตรหัสใหม่
$newHash = password_hash($new, PASSWORD_DEFAULT);
try {
  $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
  $upd->execute([$newHash, $uid]);
  echo json_encode(['success' => true]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'ไม่สามารถเปลี่ยนรหัสผ่านได้']);
}
