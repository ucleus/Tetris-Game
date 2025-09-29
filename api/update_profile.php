<?php // api/update_profile.php
require __DIR__.'/db.php';
start_session();
require_post();
if (empty($_SESSION['user_id'])) json_out(['error'=>'Auth required'],401);

$username = isset($_POST['username']) ? preg_replace('/[^a-zA-Z0-9_]/','', $_POST['username']) : null;
$theme = in_array($_POST['theme'] ?? '', ['original','red','pink','purple','babyblue']) ? $_POST['theme'] : null;
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

$pdo = pdo();
$uid = $_SESSION['user_id'];
if ($username){
  $stmt = $pdo->prepare('SELECT id FROM users WHERE username=? AND id<>?');
  $stmt->execute([$username,$uid]);
  if ($stmt->fetch()) json_out(['error'=>'Username taken'],409);
}

$sets = [];$vals=[];
if($username){ $sets[]='username=?'; $vals[]=$username; }
if($theme){ $sets[]='theme=?'; $vals[]=$theme; }
if($avatar_path){ $sets[]='avatar_path=?'; $vals[]=$avatar_path; }
if(!$sets) json_out(['ok'=>true]);
$vals[]=$uid;
$pdo->prepare('UPDATE users SET '.implode(',',$sets).' WHERE id=?')->execute($vals);
json_out(['ok'=>true,'avatar_path'=>$avatar_path]);