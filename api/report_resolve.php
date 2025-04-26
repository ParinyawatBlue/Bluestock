<?php
// api/report_resolve.php
require 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') {
  http_response_code(403);
  exit(json_encode(['success'=>false,'message'=>'Access denied']));
}

$input = json_decode(file_get_contents('php://input'), true);
$id    = isset($input['id']) ? intval($input['id']) : 0;

if ($id <= 0) {
  http_response_code(400);
  exit(json_encode(['success'=>false,'message'=>'Invalid report ID']));
}

try {
  $stmt = db()->prepare("
    UPDATE issue_reports 
       SET status='resolved', updated_at=NOW() 
     WHERE id=?
  ");
  $stmt->execute([ $id ]);
  echo json_encode(['success'=>true]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Cannot resolve report']);
}
