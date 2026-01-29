<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "META_PAGE_ACCESS_TOKEN: " . ($_ENV['META_PAGE_ACCESS_TOKEN'] ?? 'NOT SET') . "\n";
echo "META_APP_ID: " . ($_ENV['META_APP_ID'] ?? 'NOT SET') . "\n";
echo "META_APP_SECRET: " . ($_ENV['META_APP_SECRET'] ?? 'NOT SET') . "\n";
echo "META_PAGE_ID: " . ($_ENV['META_PAGE_ID'] ?? 'NOT SET') . "\n";
echo "META_VERIFY_TOKEN: " . ($_ENV['META_VERIFY_TOKEN'] ?? 'NOT SET') . "\n";

echo "\n\nUsing env() function:\n";
function env(string $key, $default = null) {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    
    if ($value === false) {
        return $default;
    }
    
    return $value;
}

echo "META_PAGE_ACCESS_TOKEN: " . env('META_PAGE_ACCESS_TOKEN', 'NOT SET') . "\n";
echo "META_APP_ID: " . env('META_APP_ID', 'NOT SET') . "\n";
echo "META_APP_SECRET: " . env('META_APP_SECRET', 'NOT SET') . "\n";
echo "META_PAGE_ID: " . env('META_PAGE_ID', 'NOT SET') . "\n";
echo "META_VERIFY_TOKEN: " . env('META_VERIFY_TOKEN', 'NOT SET') . "\n";

echo "\n\nLoading config/meta.php:\n";
$metaConfig = require __DIR__ . '/config/meta.php';
print_r($metaConfig);
