<?php
require_once __DIR__ . '/../includes/Core.php';
$core = BAF\Core::get_instance();
$beats = $core->db()->query("SELECT id, title, sample_url, sample_path FROM beats LIMIT 5")->fetchAll();
header('Content-Type: application/json');
echo json_encode($beats, JSON_PRETTY_PRINT);
