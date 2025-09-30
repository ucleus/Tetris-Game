<?php
// test-config.php (DELETE AFTER TESTING!)
require 'api/db.php';

echo "Testing configuration...\n\n";
echo "✓ Environment loaded\n";
echo "✓ DB Host: " . DB_HOST . "\n";
echo "✓ DB Name: " . DB_NAME . "\n";
echo "✓ DB User: " . DB_USER . "\n";

try {
    $pdo = pdo();
    echo "✓ Database connection successful!\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n⚠️  DELETE THIS FILE AFTER TESTING!\n";