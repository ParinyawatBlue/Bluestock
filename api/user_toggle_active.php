<?php
// api/user_toggle_active.php
require 'config.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user'])||$_SESSION['user']['role']!=='admin') {
  http_response_code(403); exit(json_encode(['success'=>false]));
}
$data = json_decode(file_get_contents('php://input'),true);
$uid = intval($data['id'] ?? 0);
$current = db()->prepare("SELECT active FROM users WHERE id=?");
$current->execute([$uid]);
$r = $current->fetch(PDO::FETCH_ASSOC);
if (!$r) { http_response_code(404); exit(json_encode(['success'=>false])); }
$new = $r['active'] ? 0 : 1;
db()->prepare("UPDATE users SET active=? WHERE id=?")->execute([$new,$uid]);
echo json_encode(['success'=>true,'active'=>$new]);
?>