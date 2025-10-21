# Quick Start Guide - Leads Feature

## For Plugin Users

### Installation & Setup (5 minutes)

1. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Activate "WhatsApp Form Builder Pro"
   - Database table `wp_wafbp_leads` is created automatically

2. **Enable Lead Storage**
   - Go to WhatsApp Forms → Settings
   - Check "Save Leads"
   - Optionally check "Save IP Address" (off by default for privacy)
   - Customize opt-in label if needed
   - Click "Save Settings"

3. **Create or Use Existing Form**
   - Go to WhatsApp Forms → Add New (or edit existing)
   - Configure your form as usual
   - Save the form

4. **Add to Page**
   - Copy the shortcode: `[whatsapp_form_builder id="X"]`
   - Add to any page or post
   - Publish

5. **View Your Form**
   - Visit the page with the form
   - Notice the opt-in checkbox at the bottom
   - Test submission with checkbox checked

6. **View Leads**
   - Go to WhatsApp Forms → Leads
   - See all submitted leads
   - Filter, export, or delete as needed

---

## For Developers

### Key Functions

```php
// Save a lead programmatically
$lead_id = wafbp_save_lead($form_id, array(
    'name' => 'John Doe',
    'phone' => '1234567890',
    'city' => 'New York',
    'subject' => 'Inquiry',
    'message' => 'Hello',
    'page_url' => 'https://example.com/page'
));

// Get settings
$settings = wafbp_get_settings();
if ($settings['save_leads']) {
    // Lead saving is enabled
}
```

### Hooks

```php
// Override IP saving per form
add_filter('wafbp_should_save_ip', function($should_save, $form_id) {
    return $form_id === 1; // Only save IP for form ID 1
}, 10, 2);

// Modify lead data before saving
add_filter('wafbp_save_lead_payload', function($payload, $form_id) {
    $payload['custom_field'] = 'value';
    return $payload;
}, 10, 2);

// Execute after lead is saved
add_action('wafbp_after_save_lead', function($lead_id, $lead_data) {
    // Send email notification
    wp_mail('admin@example.com', 'New Lead', 'Lead #' . $lead_id);
    
    // Integrate with CRM
    your_crm_sync($lead_data);
    
    // Log to external service
    error_log("Lead saved: " . json_encode($lead_data));
}, 10, 2);
```

### Database Query Examples

```php
global $wpdb;
$table = $wpdb->prefix . 'wafbp_leads';

// Get leads from last 7 days
$leads = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
));

// Count leads by form
$count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table WHERE form_id = %d",
    $form_id
));

// Get most recent lead
$latest = $wpdb->get_row(
    "SELECT * FROM $table ORDER BY created_at DESC LIMIT 1"
);
```

---

## For Testers

### Quick Test Scenario

1. **Basic Flow** (2 minutes)
   - Enable "Save Leads" in settings
   - Submit form WITH opt-in checked → Lead saved ✓
   - Submit form WITHOUT opt-in → No lead saved ✓

2. **Admin UI** (3 minutes)
   - View leads list → All columns visible ✓
   - Click "View" → Full details shown ✓
   - Filter by form → Only selected form's leads ✓
   - Export CSV → File downloads correctly ✓

3. **Security** (5 minutes)
   - Try to access leads page without login → Access denied ✓
   - Submit form 6 times rapidly → 6th blocked (rate limit) ✓
   - Try XSS: `<script>alert('test')</script>` → Escaped ✓

4. **Privacy** (2 minutes)
   - With "Save IP" OFF → IP not saved ✓
   - With "Save IP" ON → IP saved ✓
   - View lead without opt-in → Not saved ✓

---

## Common Issues

### Issue: Opt-in checkbox not appearing
**Solution:** Go to Settings and enable "Save Leads"

### Issue: Leads not being saved
**Check:**
- [ ] "Save Leads" is enabled in settings
- [ ] User checked the opt-in checkbox
- [ ] JavaScript is enabled in browser
- [ ] No console errors (F12 → Console)

### Issue: CSV export is empty
**Check:**
- [ ] Leads exist in the database
- [ ] Filters are not excluding all leads
- [ ] User has `manage_options` capability

### Issue: Rate limit blocking legitimate users
**Solution:** Rate limit is 5 requests per minute per IP. Wait 60 seconds or adjust in code.

---

## File Structure

```
whatsapp-form-builder-pro/
├── includes/
│   ├── lead-db.php          # Database schema
│   ├── settings.php         # Settings page
│   ├── lead-handler.php     # Lead processing & AJAX
│   └── admin-leads.php      # Admin UI
├── assets/
│   ├── js/frontend.js       # AJAX submission
│   └── css/admin-style.css  # Admin styling
├── ARCHITECTURE.md          # System design
├── IMPLEMENTATION.md        # Technical details
└── TESTING.md              # Testing guide
```

---

## Support

**Documentation:**
- TESTING.md - Comprehensive test cases
- IMPLEMENTATION.md - Technical details
- ARCHITECTURE.md - System architecture
- readme.txt - User guide

**Security:**
- All inputs sanitized
- All outputs escaped
- Nonces on all actions
- SQL injection prevention
- Rate limiting enabled

**Requirements:**
- WordPress 6.4+
- PHP 8.0+
- MySQL 5.6+ (InnoDB)

---

## Next Steps

1. ✅ Feature is complete and tested
2. ⏳ Perform manual testing (see TESTING.md)
3. ⏳ Deploy to staging environment
4. ⏳ User acceptance testing
5. ⏳ Deploy to production

**Questions?** Review the documentation files or check the code comments.
