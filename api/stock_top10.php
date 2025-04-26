<?php
require 'config.php';
header('Content-Type: application/json');

try {
  $stmt = db()->query("SELECT name, quantity FROM stock ORDER BY quantity DESC LIMIT 10");
  $top = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($top);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Failed to load top stock']);
}
