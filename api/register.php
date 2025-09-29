<?php // api/register.php
require __DIR__.'/db.php';
start_session();
require_post();

$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$username = preg_replace('/[^a-zA-Z0-9_]/','', $_POST['username'] ?? '');
$real = trim($_POST['real_name'] ?? '');
$theme = in_array($_POST['theme'] ?? 'original', ['original','red','pink','purple','babyblue']) ? $_POST['theme'] : 'original';

if(!$email || !$username || !$real){ json_out(['error'=>'Missing required fields'],422); }

// avatar upload (optional)
$avatar_path = null;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error']===UPLOAD_ERR_OK){
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
  if(!in_array($mime,['image/png','image/jpeg'])) json_out(['error'=>'PNG or JPG only'],415);
  $ext = $mime==='image/png' ? '.png' : '.jpg';
  $safe = bin2hex(random_bytes(8)).$ext;
  $dest = __DIR__.'/../assets/images/'.$safe;
  if(!move_uploaded_file($_FILES['avatar']['tmp_name'],$dest)) json_out(['error'=>'Upload failed'],500);
  $avatar_path = 'assets/images/'.$safe;
}

// insert
try {
  $stmt = pdo()->prepare('INSERT INTO users(email,username,real_name,avatar_path,theme) VALUES(?,?,?,?,?)');
  $stmt->execute([$email,$username,$real,$avatar_path,$theme]);
  $_SESSION['user_id'] = (int)pdo()->lastInsertId();
  json_out(['ok'=>true]);
} catch(PDOException $e){
  if(str_contains($e->getMessage(),'Duplicate')) json_out(['error'=>'Email or username already exists'],409);
  json_out(['error'=>'DB error'],500);
}