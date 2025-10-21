# WhatsApp Form Builder Pro - Webhooks Testing Guide

## Overview
This guide provides step-by-step instructions for testing the webhooks feature added in version 2.1.

## Prerequisites
- WordPress installation (4.9 or higher)
- WhatsApp Form Builder Pro plugin installed and activated
- Access to admin dashboard with `manage_options` capability
- A webhook receiver endpoint (e.g., webhook.site, requestbin.com, or custom endpoint)

## Testing Checklist

### 1. Plugin Activation
- [ ] Activate the plugin from WordPress admin
- [ ] Verify that the following database tables are created:
  - `wp_wafbp_forms`
  - `wp_wafbp_leads`
  - `wp_wafbp_webhooks`
  - `wp_wafbp_webhook_logs`
- [ ] Check that default settings are created in `wp_options`:
  - `wafbp_save_leads_enabled` (default: false)
  - `wafbp_webhook_async_enabled` (default: true)
  - `wafbp_webhook_max_attempts` (default: 4)
  - `wafbp_webhook_retry_schedule` (default: [60,300,1800,7200])
  - `wafbp_webhook_encryption_key` (random 64-char string)

### 2. Admin Menu Structure
Navigate to WordPress admin and verify the following menu items exist:
- [ ] WhatsApp Forms (main menu)
- [ ] WhatsApp Forms > All Forms
- [ ] WhatsApp Forms > Add New
- [ ] WhatsApp Forms > Webhooks (NEW)
- [ ] WhatsApp Forms > Leads (NEW)
- [ ] WhatsApp Forms > Settings (NEW)

### 3. Settings Configuration

#### Enable Lead Storage
1. Go to **WhatsApp Forms > Settings**
2. Check "Enable lead storage in database"
3. Click "Save Settings"
4. Verify success message appears

#### Configure Webhook Settings
1. In the same settings page, verify the following options:
   - [ ] "Enable asynchronous webhook delivery" (checkbox, default: checked)
   - [ ] "Max Retry Attempts" (number input, default: 4)
   - [ ] "Retry Schedule (seconds)" (text input, default: "60,300,1800,7200")
2. Optionally modify settings
3. Click "Save Settings"
4. Verify settings are saved correctly

### 4. Create a Test Form

1. Go to **WhatsApp Forms > Add New**
2. Create a simple form with:
   - Form Name: "Test Webhook Form"
   - WhatsApp Number: "1234567890" (or your actual number)
   - Enable: Name, Phone, Message fields
3. Save the form
4. Note the shortcode (e.g., `[whatsapp_form_builder id="1"]`)

### 5. Set Up Webhook Endpoint

#### Using webhook.site (Recommended for Testing)
1. Go to https://webhook.site
2. Copy the "Your unique URL" (e.g., https://webhook.site/xxxxx-xxxxx-xxxxx)
3. Keep this tab open to monitor incoming webhooks

#### Using a Custom Endpoint
If you have your own webhook receiver, ensure it:
- Accepts POST requests
- Returns 2xx status code for success
- Can verify HMAC-SHA256 signatures (optional but recommended)

### 6. Create a Webhook

1. Go to **WhatsApp Forms > Webhooks**
2. Click "Add New"
3. Fill in the form:
   - **Webhook URL**: Paste your webhook.site URL or custom endpoint
   - **Secret Key**: Enter a test secret (e.g., "test_secret_123")
   - **Target Form**: Select "Test Webhook Form" or "All Forms"
   - **Status**: Check "Active"
4. Click "Add Webhook"
5. Verify success message appears

### 7. Test Webhook (Test Button)

1. In the webhooks list, find your newly created webhook
2. Click the "Test" button
3. Verify:
   - [ ] Success message appears
   - [ ] A test payload is received at your endpoint
   - [ ] The payload contains:
     ```json
     {
       "test": true,
       "form_id": 1,
       "timestamp": "YYYY-MM-DD HH:MM:SS",
       "data": {
         "name": "Test User",
         "phone": "1234567890",
         "message": "This is a test webhook"
       }
     }
     ```
   - [ ] The `X-WAFBP-Signature` header is present

### 8. Verify Webhook Signature

Using the test webhook payload, verify the signature:

```php
<?php
$payload = '{"test":true,"form_id":1,...}'; // Full JSON received
$secret = 'test_secret_123'; // Your webhook secret
$received_signature = 'sha256=...'; // From X-WAFBP-Signature header

$expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (hash_equals($expected_signature, $received_signature)) {
    echo "✓ Signature is valid\n";
} else {
    echo "✗ Signature is invalid\n";
}
```

### 9. Test Frontend Form Submission

1. Create a test page/post and add the shortcode: `[whatsapp_form_builder id="1"]`
2. View the page on the frontend
3. Fill out the form with test data:
   - Name: "John Doe"
   - Phone: "1234567890"
   - Message: "Test message from frontend"
4. Submit the form
5. Verify:
   - [ ] WhatsApp opens with the message (existing functionality)
   - [ ] Lead is saved in database (check WhatsApp Forms > Leads)
   - [ ] Webhook is triggered and received at your endpoint
   - [ ] Payload contains the correct form data
   - [ ] `X-WAFBP-Signature` header is present and valid

### 10. View Leads

1. Go to **WhatsApp Forms > Leads**
2. Verify the submitted lead appears in the list
3. Check that the following information is displayed:
   - [ ] Lead ID
   - [ ] Form Name
   - [ ] Form Data (Name, Phone, Message)
   - [ ] Page URL
   - [ ] Submission Date/Time

### 11. Export Leads to CSV

1. On the Leads page, click "Export to CSV"
2. Verify:
   - [ ] CSV file downloads
   - [ ] File contains all leads
   - [ ] Columns include: id, form_id, created_at, page_url, name, phone, message
   - [ ] Data is properly formatted

### 12. View Webhook Logs

1. Go to **WhatsApp Forms > Webhooks**
2. Find your webhook and click "Logs"
3. Verify the following information is displayed:
   - [ ] Lead ID
   - [ ] Status Code (200 for success)
   - [ ] Attempt Number (1 for first attempt)
   - [ ] Response body (expandable)
   - [ ] Timestamp

### 13. Test Failed Webhook (Retry Logic)

#### Create a Failing Webhook
1. Go to **WhatsApp Forms > Webhooks**
2. Click "Add New"
3. Create a webhook with an invalid URL (e.g., `https://invalid-domain-that-doesnt-exist.com/webhook`)
4. Set Secret Key: "fail_test_123"
5. Target Form: Select your test form
6. Save the webhook

#### Trigger the Webhook
1. Submit a form from the frontend
2. Wait for the initial attempt to fail

#### Verify Retry Logic
1. Go to **WhatsApp Forms > Webhooks** > Click "Logs" for the failing webhook
2. Verify:
   - [ ] First attempt shows immediately (attempt_number: 1)
   - [ ] Status code is NULL or error status
   - [ ] Response contains error message

3. Check that retries are scheduled (if async is enabled):
   - Monitor logs over time
   - Verify retry attempts appear with:
     - Attempt 2: After ~1 minute
     - Attempt 3: After ~5 minutes
     - Attempt 4: After ~30 minutes
     - Attempt 5: After ~2 hours (if max attempts is 5)

### 14. Test Webhook Replay

1. From the webhook logs page (for a failed webhook)
2. Click the "Replay" button next to a failed delivery
3. Verify:
   - [ ] Success message: "Webhook queued for replay"
   - [ ] A new log entry appears with attempt_number: 1
   - [ ] The webhook is re-sent to the endpoint

### 15. Test Form-Specific vs Global Webhooks

#### Create Form-Specific Webhook
1. Create a webhook for "Form A" only
2. Submit Form A
3. Verify webhook is triggered

4. Submit Form B (different form)
5. Verify webhook is NOT triggered for Form B

#### Create Global Webhook
1. Create a webhook with Target Form: "All Forms"
2. Submit any form
3. Verify webhook is triggered

### 16. Test Multiple Webhooks

1. Create 3 webhooks for the same form (or "All Forms")
2. Submit the form
3. Verify:
   - [ ] All 3 webhooks receive the payload
   - [ ] Each webhook has its own log entry
   - [ ] Each webhook uses its own secret for signing

### 17. Test Active/Inactive Webhooks

1. Edit a webhook and uncheck "Active"
2. Save the webhook
3. Submit a form
4. Verify the inactive webhook does NOT receive payloads
5. Verify active webhooks still receive payloads

### 18. Test Webhook Deletion

1. Delete a webhook
2. Verify:
   - [ ] Webhook is removed from the list
   - [ ] Future form submissions don't trigger the deleted webhook
   - [ ] Logs for the webhook are retained (optional: verify logs are also deleted if preferred)

### 19. Plugin Uninstallation

⚠️ **Warning**: This will delete all data!

1. Deactivate the plugin
2. Delete the plugin
3. Verify the following are removed:
   - [ ] All database tables (wp_wafbp_*)
   - [ ] All options (wafbp_*)
   - [ ] All transients (wafbp_webhook_*)

### 20. Security Testing

#### Test URL Validation
1. Try to create a webhook with invalid URLs:
   - `javascript:alert('XSS')`
   - `ftp://example.com`
   - `data:text/html,<script>alert('XSS')</script>`
2. Verify all are rejected with error message

#### Test Secret Storage
1. Create a webhook with a secret
2. View the webhook in the database (wp_wafbp_webhooks table)
3. Verify the secret is encrypted (not plain text)

#### Test Capability Checks
1. Log in as a user without `manage_options` capability (e.g., Subscriber)
2. Try to access webhook pages directly
3. Verify access is denied

## Performance Testing

### Load Testing
1. Create 10 webhooks
2. Submit 100 forms rapidly
3. Monitor:
   - Server CPU/Memory usage
   - Database query performance
   - Webhook delivery times

### Async vs Sync Comparison
1. Disable async delivery (WhatsApp Forms > Settings)
2. Submit forms and measure response time
3. Enable async delivery
4. Submit forms and measure response time
5. Compare performance

## Troubleshooting

### Webhooks Not Triggering
- Verify "Save Leads" is enabled in Settings
- Check webhook is "Active"
- Verify form submissions are creating leads (check Leads page)
- Check webhook logs for error messages

### Failed Webhook Deliveries
- Verify endpoint URL is correct and accessible
- Check endpoint returns 2xx status code
- Review webhook logs for specific error messages
- Try the "Test" button to isolate issues

### Missing Webhook Logs
- Verify database tables exist
- Check for database errors in WordPress debug log
- Ensure sufficient database permissions

### Signature Verification Failures
- Verify secret matches on both ends
- Ensure payload is not modified before verification
- Use raw request body for signature calculation
- Check for character encoding issues

## Sample Webhook Receiver (PHP)

```php
<?php
// webhook-receiver.php

// Get raw POST body
$payload = file_get_contents('php://input');

// Get signature from header
$signature = $_SERVER['HTTP_X_WAFBP_SIGNATURE'] ?? '';

// Your webhook secret (from WAFBP settings)
$secret = 'test_secret_123';

// Calculate expected signature
$expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

// Verify signature
if (!hash_equals($expected_signature, $signature)) {
    http_response_code(401);
    die('Invalid signature');
}

// Signature is valid, process webhook
$data = json_decode($payload, true);

// Log to file
file_put_contents(
    'webhook-log.txt',
    date('Y-m-d H:i:s') . ' - Received: ' . print_r($data, true) . "\n\n",
    FILE_APPEND
);

// Respond with success
http_response_code(200);
echo json_encode(['status' => 'success', 'received_at' => time()]);
```

## Expected Results Summary

✓ All database tables created on activation  
✓ Admin menus appear correctly  
✓ Settings can be configured and saved  
✓ Forms can be created and managed  
✓ Webhooks can be added, edited, and deleted  
✓ Test webhooks send correctly  
✓ Frontend form submissions trigger webhooks  
✓ Leads are saved and viewable  
✓ CSV export works correctly  
✓ Webhook logs record all deliveries  
✓ Failed webhooks retry with exponential backoff  
✓ Webhook replay functionality works  
✓ HMAC signatures are valid  
✓ Secrets are encrypted in database  
✓ Security checks prevent unauthorized access  
✓ Uninstallation removes all plugin data  

## Reporting Issues

If you encounter any issues during testing, please report them with:
1. WordPress version
2. PHP version
3. Plugin version
4. Steps to reproduce
5. Expected vs actual behavior
6. Screenshots/logs if applicable
7. Browser console errors (if frontend issue)
