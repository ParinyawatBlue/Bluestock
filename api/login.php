<?php
// api/login.php
header('Content-Type: application/json');

require 'config.php';

// กรณียังไม่ได้เริ่ม session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// อ่าน JSON body
$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username']  ?? '');
$password = trim($input['password']  ?? '');

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'กรอกข้อมูลไม่ครบ'
    ]);
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, password, role FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($password, $row['password'])) {
        // ล็อกอินไม่สำเร็จ
        echo json_encode([
            'success' => false,
            'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'
        ]);
        exit;
    }

    // ล็อกอินสำเร็จ: เก็บ session
    $_SESSION['user'] = [
        'id'       => $row['id'],
        'username' => $username,
        'role'     => $row['role']
    ];

    // บันทึกประวัติการใช้งาน (login)
    $userId = $row['id'];
    $ip     = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua     = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $logStmt = $pdo->prepare("
        INSERT INTO user_logs (user_id, action, ip_address, user_agent)
        VALUES (?, 'เข้าสู่ระบบ', ?, ?)
    ");
    $logStmt->execute([$userId, $ip, $ua]);

    // ตอบกลับผลลัพธ์
    echo json_encode([
        'success' => true,
        'user'    => $_SESSION['user']
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ'
    ]);
    exit;
}
