<?php
// api/report_issue.php
require 'config.php';
header('Content-Type: application/json');

// ตรวจสอบล็อกอิน
if (!isset($_SESSION['user'])) {
  http_response_code(403);
  exit(json_encode(['success'=>false,'message'=>'ต้องเข้าสู่ระบบก่อน']));  
}

// อ่านข้อมูล
$userId   = $_SESSION['user']['id'];
$category = trim($_POST['category']  ?? '');
$title    = trim($_POST['title']     ?? '');
$detail   = trim($_POST['detail']    ?? '');

// ตรวจสอบฟิลด์บังคับ
if (!$category || !$title || !$detail) {
  http_response_code(400);
  exit(json_encode(['success'=>false,'message'=>'กรอกข้อมูลไม่ครบ']));
}

// จัดการไฟล์แนบ (ถ้ามี)
$attachment = null;
if (!empty($_FILES['attachment']['tmp_name']) && $_FILES['attachment']['error']===UPLOAD_ERR_OK) {
  $dir = __DIR__ . '/../uploads/reports';
  if (!is_dir($dir)) mkdir($dir, 0755, true);

  $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
  $fn  = uniqid('rpt_') . '.' . $ext;
  $dst = $dir . '/' . $fn;
  if (move_uploaded_file($_FILES['attachment']['tmp_name'], $dst)) {
    $attachment = '/uploads/reports/' . $fn;
  }
}

try {
  $pdo = db();
  $stmt = $pdo->prepare("
    INSERT INTO issue_reports 
      (user_id, category, title, detail, attachment, status, created_at)
    VALUES (?, ?, ?, ?, ?, 'open', NOW())
  ");
  $stmt->execute([
    $userId, $category, $title, $detail, $attachment
  ]);
  echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์']);
}
