<?php
// api/reports_list.php
require 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
  http_response_code(403);
  exit(json_encode(['error'=>'Access denied']));
}

try {
  $pdo = db();
  // ถ้า admin ให้ดูทั้งหมด ถ้าไม่ใช่ คืนเฉพาะของตัวเอง
  if ($_SESSION['user']['role']==='admin') {
    $stmt = $pdo->query("
      SELECT r.id, u.username, r.category, r.title AS subject, r.detail AS description, 
             r.attachment, r.status, r.created_at
        FROM issue_reports r
        JOIN users u ON u.id = r.user_id
       ORDER BY r.created_at DESC
    ");
  } else {
    $stmt = $pdo->prepare("
      SELECT r.id, u.username, r.category, r.title AS subject, r.detail AS description, 
             r.attachment, r.status, r.created_at
        FROM issue_reports r
        JOIN users u ON u.id = r.user_id
       WHERE r.user_id = ?
       ORDER BY r.created_at DESC
    ");
    $stmt->execute([ $_SESSION['user']['id'] ]);
  }
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($rows);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error'=>'Server error']);
}
