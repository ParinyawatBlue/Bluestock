<?php
// api/user_reset_password.php

require 'config.php';
header('Content-Type: application/json');

// (config.php เรียก session_start() ไปแล้ว ไม่ต้องเรียกซ้ำ)

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$uid   = isset($input['id']) ? intval($input['id']) : 0;
if ($uid <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// ตรวจสอบว่า user ตัวนั้นมีอยู่จริง
$stmt = db()->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$uid]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$newpass = '12345';
$hash    = password_hash($newpass, PASSWORD_DEFAULT);

try {
    $pdo = db();
    $pdo->beginTransaction();

    // อัปเดตรหัสผ่าน
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hash, $uid]);

    // บันทึก log
    $actor = $_SESSION['user']['id'];
    $ip    = $_SERVER['REMOTE_ADDR'];
    $ua    = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $logStmt = $pdo->prepare("
      INSERT INTO user_logs (user_id, action, ip_address, user_agent)
        VALUES (?, 'รีเซ็ตรหัสผ่าน', ?, ?)
    ");
    $logStmt->execute([$actor, $ip, $ua]);

    $pdo->commit();

    echo json_encode(['success' => true, 'newPassword' => $newpass]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Cannot reset password']);
}
