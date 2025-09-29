<?php // api/load_state.php
require __DIR__.'/db.php';
start_session();
require_get();
if (empty($_SESSION['user_id'])) json_out(['state'=>null]);
$stmt = pdo()->prepare('SELECT state_json FROM game_state WHERE user_id=?');
$stmt->execute([$_SESSION['user_id']]);
$row = $stmt->fetch();
json_out(['state'=>$row ? json_decode($row['state_json'],true) : null]);