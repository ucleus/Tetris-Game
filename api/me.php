<?php // api/me.php
require __DIR__.'/db.php';
start_session();
if (!empty($_SESSION['user_id'])) {
  $stmt = pdo()->prepare('SELECT id,email,username,real_name,avatar_path,theme,is_admin FROM users WHERE id=?');
  $stmt->execute([$_SESSION['user_id']]);
  $user = $stmt->fetch();
  json_out(['user'=>$user]);
}
json_out(['user'=>null]);