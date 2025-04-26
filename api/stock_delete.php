<?php
// api/stock_delete.php
require 'config.php';    // ใน config.php ต้องมี session_start() และฟังก์ชัน db()
header('Content-Type: application/json');

// 1. เช็คสิทธิ์เฉพาะ admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  http_response_code(403);
  exit(json_encode(['success'=>false,'message'=>'Access denied']));
}

// 2. รับ ID มาจาก JSON body
$input = json_decode(file_get_contents('php://input'), true);
$id    = isset($input['id']) ? intval($input['id']) : 0;
if ($id <= 0) {
  http_response_code(400);
  exit(json_encode(['success'=>false,'message'=>'Invalid ID']));
}

try {
  $pdo = db();

  // 3. (Optional) ถ้าต้องการลบไฟล์รูปภาพเก่า:
  // $old = $pdo->prepare("SELECT image FROM stock WHERE id = ?");
  // $old->execute([$id]);
  // if ($row = $old->fetch(PDO::FETCH_ASSOC)) {
  //   $path = __DIR__ . '/../' . ltrim($row['image'], '/');
  //   if (is_file($path)) unlink($path);
  // }

  // 4. ลบเรคอร์ด
  $stmt = $pdo->prepare("DELETE FROM stock WHERE id = ?");
  $stmt->execute([$id]);

  // 5. เขียน log
  $userId = $_SESSION['user']['id'];
  $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
  $ua     = $_SERVER['HTTP_USER_AGENT'] ?? '';
  $log = $pdo->prepare("
    INSERT INTO user_logs (user_id, action, ip_address, user_agent)
    VALUES (?, 'ลบสินค้า', ?, ?)
  ");
  $log->execute([$userId, $ip, $ua]);

  // 6. ตอบกลับ
  echo json_encode(['success'=>true]);

} catch (Exception $e) {
  http_response_code(500);
  error_log('stock_delete.php error: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Cannot delete record']);
}
