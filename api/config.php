<?php
// api/config.php

// เปิด session ถ้ายังไม่เริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * คืนค่า PDO instance เชื่อมต่อกับ MySQL (singleton)
 *
 * @return PDO
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=localhost;dbname=shopdb;charset=utf8mb4';
        $username = 'root';
        $password = '';
        $options = [
            // เปิดแสดงข้อผิดพลาดเป็น Exception
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // ตั้งค่า FETCH MODE เป็น associative array
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // ปิด emulate prepares เพื่อให้ใช้ native prepares
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, $username, $password, $options);
    }

    return $pdo;
}
