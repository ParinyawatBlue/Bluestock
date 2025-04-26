<?php
header('Content-Type: application/json');
require 'config.php';
$items = db()->query('SELECT * FROM stock')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($items);
