<?php
// api/user_history.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require 'config.php';

// ให้แน่ใจว่ามี session ทำงาน (config.php ควรมี session_start(); ถ้าไม่มีก็เพิ่มตรงนี้)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ตรวจสอบว่าล็อกอินแล้ว
if (empty($_SESSION['user']['id'])) {
    http_response_code(403);
    // คืนเป็น empty array แทน object เพื่อฝั่ง Vue จะ map ได้ทันที
    exit(json_encode([]));
}

// รับ user_id จาก query string หรือใช้ของตัวเอง
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (int)$_SESSION['user']['id'];

try {
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT action, ip_address, user_agent, timestamp
          FROM user_logs
         WHERE user_id = ?
         ORDER BY timestamp DESC
    ");
    $stmt->execute([$userId]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // คืนเป็น JSON array ของ log records
    echo json_encode($logs);
} catch (Exception $e) {
    http_response_code(500);
    // เกิด error คืน empty array
    echo json_encode([]);
}
