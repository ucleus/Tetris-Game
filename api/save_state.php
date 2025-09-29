<?php // api/save_state.php
require __DIR__.'/db.php';
start_session();
require_post();
if (empty($_SESSION['user_id'])) json_out(['error'=>'Auth required'],401);

$state = $_POST['state_json'] ?? '';
$json = json_decode($state,true);
if(!$json) json_out(['error'=>'Invalid JSON'],422);

$stmt = pdo()->prepare('INSERT INTO game_state(user_id,state_json) VALUES(?,JSON_OBJECT()) ON DUPLICATE KEY UPDATE state_json=VALUES(state_json)');
$stmt->execute([$_SESSION['user_id']]);

// safer: update with bound param
$stmt = pdo()->prepare('UPDATE game_state SET state_json=? WHERE user_id=?');
$stmt->execute([$state, $_SESSION['user_id']]);

json_out(['ok'=>true]);