<?php // admin/index.php
require __DIR__.'/../api/db.php';
start_session();
if (empty($_SESSION['user_id'])) die('Login required');
$pdo = pdo();
$me = $pdo->prepare('SELECT is_admin FROM users WHERE id=?');
$me->execute([$_SESSION['user_id']]);
$me=$me->fetch();
if(!$me || !$me['is_admin']) die('Forbidden');
$users = $pdo->query('SELECT id,email,username,real_name,theme,created_at FROM users ORDER BY created_at DESC')->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Users</title>
<style>body{font-family:system-ui,Segoe UI,Arial,sans-serif;padding:24px}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:8px}th{background:#f7f7f7;text-align:left}</style>
</head><body>
<h1>Users</h1>
<table>
<tr><th>ID</th><th>Email</th><th>Username</th><th>Name</th><th>Theme</th><th>Created</th></tr>
<?php foreach($users as $u): ?>
<tr>
<td><?=htmlspecialchars($u['id'])?></td>
<td><?=htmlspecialchars($u['email'])?></td>
<td><?=htmlspecialchars($u['username'])?></td>
<td><?=htmlspecialchars($u['real_name'])?></td>
<td><?=htmlspecialchars($u['theme'])?></td>
<td><?=htmlspecialchars($u['created_at'])?></td>
</tr>
<?php endforeach; ?>
</table>
</body></html>