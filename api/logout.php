<?php
// api/logout.php
header('Content-Type: application/json');
require 'config.php';
    session_start();
}

// ถ้ามี user session ให้บันทึก log การ logout
if (isset($_SESSION['user']['id'])) {
    $userId = $_SESSION['user']['id'];
    $ip     = $_SERVER['REMOTE_ADDR']        ?? null;
    $ua     = $_SERVER['HTTP_USER_AGENT']    ?? null;

    // บันทึก action='logout'
    $stmt = db()->prepare("
      INSERT INTO user_logs (user_id, action, ip_address, user_agent)
      VALUES (?, 'ออกจากระบบ', ?, ?)
    ");
    $stmt->execute([$userId, $ip, $ua]);
}

// ทำลาย session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

echo json_encode(['success' => true]);
exit;
