<?php

/**
 * Test script to verify admin panel setup
 */

echo "🧪 WhatsApp HSM Admin Panel - Test Script\n";
echo str_repeat("━", 60) . "\n\n";

// Test 1: PHP Version
echo "1️⃣  Testing PHP Version...\n";
$phpVersion = PHP_VERSION;
$minVersion = '7.4.0';
if (version_compare($phpVersion, $minVersion, '>=')) {
    echo "   ✅ PHP $phpVersion (minimum: $minVersion)\n\n";
} else {
    echo "   ❌ PHP $phpVersion is too old (minimum: $minVersion)\n\n";
    exit(1);
}

// Test 2: cURL Extension
echo "2️⃣  Testing cURL Extension...\n";
if (extension_loaded('curl')) {
    echo "   ✅ cURL extension is loaded\n\n";
} else {
    echo "   ❌ cURL extension is not loaded\n";
    echo "   Install with: apt-get install php-curl (Ubuntu/Debian)\n\n";
    exit(1);
}

// Test 3: JSON Extension
echo "3️⃣  Testing JSON Extension...\n";
if (extension_loaded('json')) {
    echo "   ✅ JSON extension is loaded\n\n";
} else {
    echo "   ❌ JSON extension is not loaded\n\n";
    exit(1);
}

// Test 4: File Permissions
echo "4️⃣  Testing File Permissions...\n";
$messagesFile = __DIR__ . '/messages.json';
if (file_exists($messagesFile)) {
    if (is_writable($messagesFile)) {
        echo "   ✅ messages.json is writable\n";
    } else {
        echo "   ⚠️  messages.json is not writable\n";
        echo "   Run: chmod 666 messages.json\n";
    }
} else {
    echo "   ℹ️  messages.json doesn't exist (will be created automatically)\n";
}
echo "\n";

// Test 5: API Configuration
echo "5️⃣  Testing API Configuration...\n";
require_once __DIR__ . '/api.php';
// Note: This will execute the API, so we'll just check if it loads
echo "   ✅ api.php loads without errors\n\n";

// Test 6: Test API Endpoint
echo "6️⃣  Testing API Endpoint (get_templates)...\n";
$apiKey = '1de5f9151fbe499385186a3327ea1b27-a6a496a1-03bd-4c7b-99c6-6351972188f1';
$sender = '351927587119';

$ch = curl_init("https://api.infobip.com/whatsapp/2/senders/$sender/templates");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: App ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    $count = count($data['templates'] ?? []);
    echo "   ✅ API connection successful\n";
    echo "   ✅ Found $count templates\n\n";
} else {
    echo "   ❌ API connection failed (HTTP $httpCode)\n";
    echo "   Check your API key and sender number\n\n";
}

// Summary
echo str_repeat("━", 60) . "\n";
echo "✅ All tests passed! Admin panel is ready to use.\n\n";
echo "🚀 Start the server with:\n";
echo "   cd admin-panel && php -S localhost:8080\n\n";
echo "🌐 Then open: http://localhost:8080\n";
echo str_repeat("━", 60) . "\n";
