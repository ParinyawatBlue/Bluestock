<?php
// api/user_get.php
require 'config.php';
header('Content-Type: application/json');

// ตรวจสอบ session ก่อน
if (!isset($_SESSION['user'])) {
  http_response_code(403);
  exit(json_encode(['error'=>'Access denied']));
}

// อ่าน id จาก query string
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  exit(json_encode(['error'=>'Invalid user id']));
}

try {
  $stmt = db()->prepare("SELECT id, username, role, created_at FROM users WHERE id = ?");
  $stmt->execute([$id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$user) {
    http_response_code(404);
    exit(json_encode(['error'=>'User not found']));
  }
  echo json_encode($user);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error'=>'Server error']);
}
