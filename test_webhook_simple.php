<?php
/**
 * Simple webhook test script
 * Place this in the public directory and access it via browser
 */

$webhookUrl = 'https://valyutalar.e-qarz.uz/telegram/webhook';
$logFile = __DIR__ . '/../storage/logs/webhook-debug.log';

// Create log directory if not exists
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Test data
$testData = [
    'update_id' => 999999999,
    'message' => [
        'message_id' => 1,
        'from' => [
            'id' => 123456789,
            'is_bot' => false,
            'first_name' => 'Test',
            'username' => 'testuser',
        ],
        'chat' => [
            'id' => 123456789,
            'first_name' => 'Test',
            'username' => 'testuser',
            'type' => 'private',
        ],
        'date' => time(),
        'text' => '/start',
        'entities' => [
            [
                'offset' => 0,
                'length' => 6,
                'type' => 'bot_command',
            ],
        ],
    ],
];

// Log test
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] TEST SCRIPT RUN\n", FILE_APPEND);

// Send test request
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Log result
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] TEST RESULT: HTTP $httpCode | Response: $response | Error: $error\n", FILE_APPEND);

echo "<h1>Webhook Test</h1>";
echo "<p><strong>Webhook URL:</strong> $webhookUrl</p>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";
if ($error) {
    echo "<p><strong>Error:</strong> " . htmlspecialchars($error) . "</p>";
}
echo "<p><strong>Log File:</strong> $logFile</p>";
echo "<p><strong>Log File Exists:</strong> " . (file_exists($logFile) ? 'Yes' : 'No') . "</p>";
if (file_exists($logFile)) {
    echo "<p><strong>Log File Size:</strong> " . filesize($logFile) . " bytes</p>";
    echo "<p><strong>Last 20 lines of log:</strong></p>";
    echo "<pre>" . htmlspecialchars(implode('', array_slice(file($logFile), -20))) . "</pre>";
}

