<?php // api/awards.php
require __DIR__.'/db.php';
start_session();
$pdo = pdo();
if($_SERVER['REQUEST_METHOD']==='GET'){
  if (empty($_SESSION['user_id'])) json_out(['awards'=>[]]);
  $stmt = $pdo->prepare('SELECT a.code,a.name,a.description,ua.unlocked_at FROM user_awards ua JOIN awards a ON a.id=ua.award_id WHERE ua.user_id=? ORDER BY ua.unlocked_at DESC');
  $stmt->execute([$_SESSION['user_id']]);
  json_out(['awards'=>$stmt->fetchAll()]);
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  if (empty($_SESSION['user_id'])) json_out(['error'=>'Auth required'],401);
  $code = preg_replace('/[^A-Z0-9_]/','', $_POST['code'] ?? '');
  if(!$code) json_out(['error'=>'Bad code'],422);
  $stmt=$pdo->prepare('SELECT id FROM awards WHERE code=?');
  $stmt->execute([$code]);
  $a=$stmt->fetch(); if(!$a) json_out(['error'=>'Unknown award'],404);
  $stmt=$pdo->prepare('INSERT IGNORE INTO user_awards(user_id,award_id) VALUES(?,?)');
  $stmt->execute([$_SESSION['user_id'],$a['id']]);
  json_out(['ok'=>true]);
}
json_out(['error'=>'Method not allowed'],405);