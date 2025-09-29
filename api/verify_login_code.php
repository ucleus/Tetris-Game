<?php // api/verify_login_code.php
require __DIR__.'/db.php';
start_session();
require_post();
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$code  = trim($_POST['code'] ?? '');
if(!$email || !preg_match('/^\d{6}$/',$code)) json_out(['error'=>'Bad input'],422);

$hash = hash('sha256', $code);
$now = (new DateTime())->format('Y-m-d H:i:s');

$pdo = pdo();
$pdo->beginTransaction();
$stmt = $pdo->prepare('SELECT id,expires_at,used FROM login_codes WHERE email=? AND code_hash=? ORDER BY id DESC LIMIT 1');
$stmt->execute([$email,$hash]);
$row = $stmt->fetch();
if(!$row || $row['used'] || $row['expires_at'] < $now){ $pdo->rollBack(); json_out(['error'=>'Invalid/expired code'],401); }
$pdo->prepare('UPDATE login_codes SET used=1 WHERE id=?')->execute([$row['id']]);

// Upsert user shell if not found (allows email-only quick login)
$stmt = $pdo->prepare('SELECT id FROM users WHERE email=?');
$stmt->execute([$email]);
$u = $stmt->fetch();
if(!$u){
  $pdo->prepare('INSERT INTO users(email,username,real_name) VALUES(?,?,?)')->execute([$email, 'user'.random_int(1000,9999), '']);
  $uid = (int)$pdo->lastInsertId();
} else { $uid = (int)$u['id']; }
$pdo->commit();

$_SESSION['user_id'] = $uid;
json_out(['ok'=>true]);