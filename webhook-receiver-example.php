<?php
/**
 * Sample Webhook Receiver for WhatsApp Form Builder Pro
 * 
 * This is an example webhook receiver that can be used to test
 * the webhook functionality of WhatsApp Form Builder Pro.
 * 
 * Usage:
 * 1. Upload this file to your web server
 * 2. Make it accessible via URL (e.g., https://yourdomain.com/webhook-receiver.php)
 * 3. Use that URL when creating a webhook in WAFBP admin
 * 4. Set the secret to match the one defined below
 * 
 * Features:
 * - Validates HMAC-SHA256 signature
 * - Logs all webhook requests to a file
 * - Returns appropriate HTTP status codes
 * - Handles errors gracefully
 */

// Configuration
define('WEBHOOK_SECRET', 'your_webhook_secret_here'); // Change this to your actual secret
define('LOG_FILE', __DIR__ . '/webhook-logs.txt'); // Path to log file
define('ENABLE_SIGNATURE_VALIDATION', true); // Set to false to disable signature validation (not recommended)

/**
 * Log a message to file
 */
function log_message($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = sprintf("[%s] %s\n", $timestamp, $message);
    file_put_contents(LOG_FILE, $log_entry, FILE_APPEND);
}

/**
 * Send JSON response
 */
function send_response($status_code, $data) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    log_message('ERROR: Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    send_response(405, [
        'error' => 'Method not allowed',
        'message' => 'This endpoint only accepts POST requests'
    ]);
}

// Get raw POST body
$payload = file_get_contents('php://input');

if (empty($payload)) {
    log_message('ERROR: Empty payload received');
    send_response(400, [
        'error' => 'Bad request',
        'message' => 'Empty payload'
    ]);
}

// Get signature from header
$signature = isset($_SERVER['HTTP_X_WAFBP_SIGNATURE']) ? $_SERVER['HTTP_X_WAFBP_SIGNATURE'] : '';

// Validate signature if enabled
if (ENABLE_SIGNATURE_VALIDATION) {
    if (empty($signature)) {
        log_message('ERROR: Missing signature header');
        send_response(401, [
            'error' => 'Unauthorized',
            'message' => 'Missing X-WAFBP-Signature header'
        ]);
    }

    // Calculate expected signature
    $expected_signature = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);

    // Compare signatures (timing-safe comparison)
    if (!hash_equals($expected_signature, $signature)) {
        log_message('ERROR: Invalid signature');
        log_message('  Expected: ' . $expected_signature);
        log_message('  Received: ' . $signature);
        send_response(401, [
            'error' => 'Unauthorized',
            'message' => 'Invalid signature'
        ]);
    }

    log_message('✓ Signature validated successfully');
}

// Decode JSON payload
$data = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    log_message('ERROR: Invalid JSON: ' . json_last_error_msg());
    send_response(400, [
        'error' => 'Bad request',
        'message' => 'Invalid JSON: ' . json_last_error_msg()
    ]);
}

// Log the webhook data
log_message('=== Webhook Received ===');
log_message('Lead ID: ' . ($data['lead_id'] ?? 'N/A'));
log_message('Form ID: ' . ($data['form_id'] ?? 'N/A'));
log_message('Timestamp: ' . ($data['timestamp'] ?? 'N/A'));
log_message('Page URL: ' . ($data['page_url'] ?? 'N/A'));

if (isset($data['data']) && is_array($data['data'])) {
    log_message('Form Data:');
    foreach ($data['data'] as $key => $value) {
        log_message('  ' . $key . ': ' . $value);
    }
}

log_message('Full Payload: ' . $payload);
log_message('========================');

// Process the webhook data
// This is where you would add your custom logic, such as:
// - Save to your own database
// - Send email notifications
// - Integrate with CRM
// - Trigger other actions

// Example: Send email notification
if (function_exists('mail') && isset($data['data'])) {
    $to = 'admin@example.com'; // Change to your email
    $subject = 'New Lead from WhatsApp Form';
    $message = sprintf(
        "New lead received:\n\n" .
        "Lead ID: %s\n" .
        "Form ID: %s\n" .
        "Name: %s\n" .
        "Phone: %s\n" .
        "Message: %s\n" .
        "Page: %s\n" .
        "Time: %s\n",
        $data['lead_id'] ?? 'N/A',
        $data['form_id'] ?? 'N/A',
        $data['data']['name'] ?? 'N/A',
        $data['data']['phone'] ?? 'N/A',
        $data['data']['message'] ?? 'N/A',
        $data['page_url'] ?? 'N/A',
        $data['timestamp'] ?? 'N/A'
    );
    
    // Uncomment to enable email notifications
    // mail($to, $subject, $message);
    // log_message('✓ Email notification sent to ' . $to);
}

// Example: Save to custom database
// Uncomment and modify for your database structure
/*
try {
    $pdo = new PDO('mysql:host=localhost;dbname=your_db', 'username', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("
        INSERT INTO webhook_leads (lead_id, form_id, name, phone, message, page_url, created_at)
        VALUES (:lead_id, :form_id, :name, :phone, :message, :page_url, :created_at)
    ");
    
    $stmt->execute([
        ':lead_id' => $data['lead_id'] ?? null,
        ':form_id' => $data['form_id'] ?? null,
        ':name' => $data['data']['name'] ?? null,
        ':phone' => $data['data']['phone'] ?? null,
        ':message' => $data['data']['message'] ?? null,
        ':page_url' => $data['page_url'] ?? null,
        ':created_at' => $data['timestamp'] ?? date('Y-m-d H:i:s'),
    ]);
    
    log_message('✓ Saved to database');
} catch (PDOException $e) {
    log_message('ERROR: Database error: ' . $e->getMessage());
}
*/

// Example: Integrate with external API
// Uncomment and modify for your API
/*
$api_url = 'https://api.example.com/leads';
$api_key = 'your_api_key_here';

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code >= 200 && $http_code < 300) {
    log_message('✓ Sent to external API');
} else {
    log_message('ERROR: External API error: ' . $response);
}
*/

// Send success response
send_response(200, [
    'status' => 'success',
    'message' => 'Webhook processed successfully',
    'received_at' => time(),
    'lead_id' => $data['lead_id'] ?? null
]);
