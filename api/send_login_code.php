<?php // api/send_login_code.php
require __DIR__.'/db.php';
require_post();
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
if(!$email) json_out(['error'=>'Invalid email'],422);

$code = random_int(100000,999999);
$hash = hash('sha256', (string)$code);
$exp = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

$stmt = pdo()->prepare('INSERT INTO login_codes(email,code_hash,expires_at) VALUES(?,?,?)');
$stmt->execute([$email,$hash,$exp]);

// naive email via PHP mail(); on Hostinger this usually works.
$subject = 'Your Tetris sign-in code';
$body = "Your one-time code is: $code (valid for 10 minutes).";
$headers = 'From: no-reply@'.($_SERVER['HTTP_HOST'] ?? 'localhost');
@mail($email, $subject, $body, $headers);

json_out(['ok'=>true]);