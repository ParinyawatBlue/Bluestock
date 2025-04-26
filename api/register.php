<?php
// api/register.php
require 'config.php';    // ใน config.php ควรมี session_start() และฟังก์ชัน db()
header('Content-Type: application/json');

// 1. ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  http_response_code(403);
  exit(json_encode(['success'=>false,'message'=>'Access denied']));
}

// 2. อ่าน JSON input
$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$role     = $input['role']     ?? 'viewer';

// 3. ตรวจสอบฟิลด์
if ($username === '' || $password === '') {
  http_response_code(400);
  exit(json_encode(['success'=>false,'message'=>'กรุณาระบุ username และ password']));
}

// 4. ความยาวรหัสผ่านขั้นต่ำ
if (strlen($password) < 6) {
  http_response_code(400);
  exit(json_encode(['success'=>false,'message'=>'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร']));
}

// 5. ตรวจสอบ role ให้ถูกต้อง
$validRoles = ['admin','staff','viewer'];
if (!in_array($role, $validRoles, true)) {
  http_response_code(400);
  exit(json_encode(['success'=>false,'message'=>'Role ไม่ถูกต้อง']));
}

try {
  $pdo = db();

  // 6. เช็คซ้ำ username
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
  $stmt->execute([$username]);
  if ($stmt->fetchColumn() > 0) {
    http_response_code(409);
    exit(json_encode(['success'=>false,'message'=>'ชื่อผู้ใช้นี้มีอยู่แล้ว']));
  }

  // 7. แฮช password และ insert
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $ins  = $pdo->prepare('INSERT INTO users (username,password,role) VALUES (?,?,?)');
  $ins->execute([$username,$hash,$role]);

  echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
} catch (Exception $e) {
  http_response_code(500);
  error_log("Register error: ".$e->getMessage());
  exit(json_encode(['success'=>false,'message'=>'เกิดข้อผิดพลาดภายในระบบ กรุณาลองใหม่ภายหลัง']));
}
