<?php
// api/users_list.php
require 'config.php';
header('Content-Type: application/json');
// เฉพาะ admin เท่านั้น
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') {
  http_response_code(403);
  exit(json_encode([]));
}
$users = db()->query("SELECT id, username, role, active, created_at FROM users")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($users);
