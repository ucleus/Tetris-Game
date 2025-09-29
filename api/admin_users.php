<?php // api/admin_users.php
require __DIR__.'/db.php';
start_session();
if (empty($_SESSION['user_id'])) json_out(['error'=>'Auth required'],401);
$pdo = pdo();
$me = $pdo->prepare('SELECT is_admin FROM users WHERE id=?');
$me->execute([$_SESSION['user_id']]);
$me = $me->fetch();
if (!$me || !$me['is_admin']) json_out(['error'=>'Forbidden'],403);

$rows = $pdo->query('SELECT id,email,username,real_name,theme,created_at FROM users ORDER BY created_at DESC LIMIT 200')->fetchAll();
json_out(['users'=>$rows]);