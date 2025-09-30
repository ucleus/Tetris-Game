<?php // api/save_state.php
require __DIR__.'/db.php';
start_session();
require_post();

if (empty($_SESSION['user_id'])) {
  json_out(['error'=>'Auth required'], 401);
}

$state = $_POST['state_json'] ?? '';

// Validate JSON format
$json = json_decode($state, true);
if (!$json) {
  json_out(['error'=>'Invalid JSON'], 422);
}

// Validate JSON structure (basic validation)
$required_keys = ['grid', 'score', 'lines', 'level'];
foreach ($required_keys as $key) {
  if (!isset($json[$key])) {
    json_out(['error'=>"Missing required field: $key"], 422);
  }
}

// Additional validation
if (!is_array($json['grid']) || !is_numeric($json['score']) || !is_numeric($json['level'])) {
  json_out(['error'=>'Invalid game state format'], 422);
}

$pdo = pdo();
$uid = $_SESSION['user_id'];

// Single efficient query with proper parameter binding
$stmt = $pdo->prepare('
  INSERT INTO game_state (user_id, state_json) 
  VALUES (?, ?)
  ON DUPLICATE KEY UPDATE 
    state_json = VALUES(state_json),
    updated_at = CURRENT_TIMESTAMP
');

$stmt->execute([$uid, $state]);

json_out(['ok'=>true]);