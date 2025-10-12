<?php
// Test API authentication

echo "🧪 Testing SIAKAD SMK API...\n\n";

// Test login endpoint
$loginData = [
    'email' => 'admin@demo.com',
    'password' => 'password'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/login');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    echo "❌ Could not connect to API. Make sure the server is running:\n";
    echo "   php artisan serve\n\n";
    exit(1);
}

echo "📡 API Login Test:\n";
echo "   HTTP Status: $httpCode\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['token'])) {
        echo "✅ Login successful!\n";
        echo "   User: " . ($data['user']['name'] ?? 'Unknown') . "\n";
        echo "   Role: " . ($data['user']['role'] ?? 'Unknown') . "\n";
        echo "   Token: " . substr($data['token'], 0, 20) . "...\n";
    } else {
        echo "❌ Login failed - no token received\n";
    }
} else {
    echo "❌ Login failed - HTTP $httpCode\n";
    echo "   Response: " . substr($response, 0, 200) . "...\n";
}

echo "\n📋 Manual Test Instructions:\n";
echo "1. Start server: php artisan serve\n";
echo "2. Visit: http://localhost:8000\n";
echo "3. Login with: admin@demo.com / password\n";
echo "4. Explore the dashboard\n\n";

echo "🎉 API structure is ready for testing!\n";