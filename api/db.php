<?php // api/db.php
// Set your Hostinger DB credentials
const DB_HOST = 'localhost';
const DB_NAME = 'u652263477_gamingground';
const DB_USER = 'u652263477_seandiadmin';
const DB_PASS = 'z[7U/@71#6';

function pdo() : PDO {
  static $pdo; if ($pdo) return $pdo;
  $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
  $pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}

function start_session() {
  if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
      'lifetime' => 0,
      'path' => '/',
      'httponly' => true,
      'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
      'samesite' => 'Lax',
    ]);
    session_start();
  }
}

function json_out($data, $code=200){
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

function require_post(){ if($_SERVER['REQUEST_METHOD']!=='POST') json_out(['error'=>'POST only'],405); }
function require_get(){ if($_SERVER['REQUEST_METHOD']!=='GET') json_out(['error'=>'GET only'],405); }