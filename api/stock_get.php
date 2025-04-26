<?php
header('Content-Type: application/json');
require 'config.php';
$id = intval($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM stock WHERE id=?');
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($item ?: []);
