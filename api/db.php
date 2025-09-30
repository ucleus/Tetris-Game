<?php // api/db.php

// Load environment variables from .env file
function loadEnv($path) {
  if (!file_exists($path)) {
    die('Configuration file not found. Please create .env file in the root directory.');
  }
  
  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $line) {
    // Skip comments and empty lines
    $line = trim($line);
    if (empty($line) || strpos($line, '#') === 0) {
      continue;
    }
    
    // Parse KEY=VALUE
    if (strpos($line, '=') !== false) {
      list($key, $value) = explode('=', $line, 2);
      $key = trim($key);
      $value = trim($value);
      
      // Remove surrounding quotes if present
      if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
        $value = $matches[2];
      }
      
      // Set as environment variable if not already set
      if (!getenv($key)) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
      }
    }
  }
}

// Load .env file from parent directory (one level up from api/)
loadEnv(__DIR__ . '/../.env');

// Helper function to get environment variables with defaults
function env($key, $default = null) {
  $value = getenv($key);
  return $value !== false ? $value : $default;
}

// Database configuration from environment variables
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME'));
define('DB_USER', env('DB_USER'));
define('DB_PASS', env('DB_PASS'));

// Validate required environment variables
if (!DB_NAME || !DB_USER || !DB_PASS) {
  die('Missing required database configuration. Please check your .env file.');
}

function pdo() : PDO {
  static $pdo;
  if ($pdo) return $pdo;
  
  $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
  
  try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false, // Better security
    ]);
  } catch (PDOException $e) {
    // Log error securely without exposing credentials
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed. Please check your configuration.');
  }
  
  return $pdo;
}

function start_session() {
  if (session_status() === PHP_SESSION_NONE) {
    // Get session configuration from .env
    $isSecure = env('SESSION_SECURE', 'true') === 'true';
    $domain = env('SESSION_DOMAIN', '');
    
    session_set_cookie_params([
      'lifetime' => (int)env('SESSION_LIFETIME', 0),
      'path' => '/',
      'domain' => $domain,
      'httponly' => true,
      'secure' => $isSecure,
      'samesite' => 'Lax',
    ]);
    session_start();
  }
}

function json_out($data, $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

function require_post() {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['error' => 'POST only'], 405);
  }
}

function require_get() {
  if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_out(['error' => 'GET only'], 405);
  }
}

// CSRF Token Generation and Validation
function generate_csrf_token() {
  if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes((int)env('CSRF_TOKEN_LENGTH', 32)));
  }
  return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
  if (!isset($_SESSION['csrf_token'])) {
    return false;
  }
  return hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf() {
  $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
  if (!validate_csrf_token($token)) {
    json_out(['error' => 'Invalid CSRF token'], 403);
  }
}

// Rate Limiting Helper
function check_rate_limit($key, $max_attempts = null, $window = null) {
  $max_attempts = $max_attempts ?? (int)env('RATE_LIMIT_LOGIN_ATTEMPTS', 5);
  $window = $window ?? (int)env('RATE_LIMIT_LOGIN_WINDOW', 300); // seconds
  
  $cache_key = "rate_limit:" . $key;
  $cache_file = sys_get_temp_dir() . '/' . md5($cache_key) . '.json';
  
  $data = [];
  if (file_exists($cache_file)) {
    $content = file_get_contents($cache_file);
    $data = json_decode($content, true) ?: [];
  }
  
  $now = time();
  
  // Clean old attempts outside the window
  if (isset($data['attempts'])) {
    $data['attempts'] = array_filter($data['attempts'], function($timestamp) use ($now, $window) {
      return $timestamp > ($now - $window);
    });
    $data['attempts'] = array_values($data['attempts']); // Re-index
  } else {
    $data['attempts'] = [];
  }
  
  // Check if rate limited
  if (count($data['attempts']) >= $max_attempts) {
    $oldest = min($data['attempts']);
    $retry_after = ($oldest + $window) - $now;
    json_out([
      'error' => 'Too many requests. Please try again later.',
      'retry_after' => max(1, $retry_after)
    ], 429);
  }
  
  // Add current attempt
  $data['attempts'][] = $now;
  file_put_contents($cache_file, json_encode($data));
}

// Clear rate limit for a key (useful after successful login)
function clear_rate_limit($key) {
  $cache_key = "rate_limit:" . $key;
  $cache_file = sys_get_temp_dir() . '/' . md5($cache_key) . '.json';
  if (file_exists($cache_file)) {
    unlink($cache_file);
  }
}