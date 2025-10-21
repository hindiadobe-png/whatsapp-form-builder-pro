=== WhatsApp Form Builder Pro ===
Contributors: yourname
Tags: whatsapp, contact form, shortcode
Requires at least: 4.9
Tested up to: 6.4
Stable tag: 2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create customizable WhatsApp contact forms and send page context with one click.

== Description ==
This plugin creates WhatsApp contact forms with shortcode support, styling options and RTL support. Includes lead storage, webhook integration, and CSV export.

== Installation ==
1. Upload plugin files to /wp-content/plugins/whatsapp-form-builder-pro
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to WhatsApp Forms -> Add New to create a form

== Features ==
* Create unlimited WhatsApp contact forms
* Customizable styling with color picker
* RTL language support
* Lead storage and management
* Webhook integration for external services
* CSV export of leads
* HMAC-SHA256 webhook signatures for security
* Automatic retry with exponential backoff for failed webhooks

== Webhooks ==

= What are Webhooks? =
Webhooks allow you to send form submission data to external services automatically. When a user submits a form, the plugin will send a POST request with the lead data to your configured webhook URL.

= Setting Up Webhooks =
1. Go to WhatsApp Forms -> Webhooks
2. Click "Add New"
3. Enter your webhook URL (must be HTTP or HTTPS)
4. Enter a secret key for signing webhooks
5. Choose target form or "All Forms"
6. Save the webhook

= Webhook Security =
All webhooks are signed using HMAC-SHA256. The signature is sent in the `X-WAFBP-Signature` header.

Example signature verification in PHP:
```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WAFBP_SIGNATURE'] ?? '';
$secret = 'your_webhook_secret';

$expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (hash_equals($expected_signature, $signature)) {
    // Valid signature
    $data = json_decode($payload, true);
    // Process webhook...
} else {
    // Invalid signature
    http_response_code(401);
}
```

= Webhook Payload Format =
```json
{
    "lead_id": 123,
    "form_id": 1,
    "data": {
        "name": "John Doe",
        "phone": "1234567890",
        "message": "Hello, I'm interested"
    },
    "page_url": "https://example.com/contact",
    "timestamp": "2024-01-01 12:00:00"
}
```

= Testing Webhooks =
You can test webhooks using curl:
```bash
curl -X POST https://your-webhook-url.com/endpoint \
  -H "Content-Type: application/json" \
  -H "X-WAFBP-Signature: sha256=your_signature_here" \
  -d '{"lead_id":1,"form_id":1,"data":{"name":"Test"}}'
```

= Webhook Retry Logic =
Failed webhooks are automatically retried with exponential backoff:
- Attempt 1: Immediate
- Attempt 2: After 1 minute
- Attempt 3: After 5 minutes
- Attempt 4: After 30 minutes
- Attempt 5: After 2 hours

You can configure retry settings in WhatsApp Forms -> Settings.

== Changelog ==
= 2.1 =
* Added webhook support with HMAC-SHA256 signatures
* Added lead storage and management
* Added CSV export functionality
* Added automatic webhook retry with exponential backoff
* Added webhook logs and monitoring
* Added settings page for webhook configuration

= 2.0 =
* Security and code quality improvements
* Move inline JS/CSS to assets, sanitize inputs and outputs