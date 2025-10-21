# Testing Guide for Leads Feature

## Prerequisites
- WordPress 6.4+
- PHP 8.0+
- Plugin activated

## Testing Checklist

### 1. Plugin Activation
- [ ] Activate plugin from WordPress admin
- [ ] Verify `wp_wafbp_leads` table is created in database
- [ ] Check table structure has all required columns:
  - id (bigint, auto_increment)
  - form_id (mediumint)
  - name (varchar 255)
  - phone (varchar 50)
  - city (varchar 255)
  - subject (varchar 255)
  - message (text)
  - page_url (varchar 500)
  - ip_address (varchar 100, nullable)
  - user_agent (varchar 500, nullable)
  - created_at (datetime)
- [ ] Verify indexes exist on form_id and created_at

### 2. Settings Page
- [ ] Navigate to WhatsApp Forms → Settings
- [ ] Verify all settings are visible:
  - Save Leads (checkbox)
  - Save IP Address (checkbox)
  - Opt-in Label (text input)
  - Data Retention Days (number input)
  - Delete Data on Uninstall (checkbox)
- [ ] Test saving settings with default values
- [ ] Enable "Save Leads" option
- [ ] Verify "Save IP Address" is OFF by default
- [ ] Test custom opt-in label text
- [ ] Verify success message appears on save
- [ ] Test settings persistence (refresh page)

### 3. Form Display
- [ ] Create a new WhatsApp form (or use existing)
- [ ] Place shortcode on a test page
- [ ] Visit page with "Save Leads" DISABLED in settings
- [ ] Verify NO opt-in checkbox appears
- [ ] Enable "Save Leads" in settings
- [ ] Refresh the page
- [ ] Verify opt-in checkbox appears with correct label
- [ ] Verify checkbox is properly styled and aligned

### 4. Lead Submission - Without Opt-in
- [ ] Fill out form completely
- [ ] Do NOT check the opt-in checkbox
- [ ] Submit form
- [ ] Verify WhatsApp opens as normal
- [ ] Navigate to WhatsApp Forms → Leads
- [ ] Verify NO new lead was saved

### 5. Lead Submission - With Opt-in
- [ ] Fill out form with test data:
  - Name: "Test User"
  - Phone: "1234567890"
  - City: "Test City"
  - Subject: "Test Subject"
  - Message: "Test message content"
- [ ] Check the opt-in checkbox
- [ ] Submit form
- [ ] Verify WhatsApp opens as normal
- [ ] Navigate to WhatsApp Forms → Leads
- [ ] Verify new lead appears in list with correct data
- [ ] Verify form name is displayed correctly
- [ ] Verify date/time is displayed correctly

### 6. Lead Details View
- [ ] Click "View" on a lead
- [ ] Verify all fields are displayed:
  - ID
  - Form name
  - Name
  - Phone
  - City
  - Subject
  - Message
  - Page URL
  - Date/time
- [ ] Verify IP address is NOT shown (if Save IP is disabled)
- [ ] Verify User Agent is NOT shown (if Save IP is disabled)
- [ ] Click "← Back to Leads" button
- [ ] Verify you return to leads list

### 7. IP Address Capture
- [ ] Enable "Save IP Address" in settings
- [ ] Submit a new form with opt-in checked
- [ ] View the lead details
- [ ] Verify IP address is now displayed
- [ ] Verify User Agent is now displayed
- [ ] Disable "Save IP Address" in settings
- [ ] Submit another form
- [ ] Verify new lead has NULL IP and User Agent

### 8. Lead Filtering
- [ ] Create and submit forms from different form IDs
- [ ] In Leads page, select a specific form from dropdown
- [ ] Click "Filter"
- [ ] Verify only leads from selected form are shown
- [ ] Select "All Forms" and verify all leads appear
- [ ] Test date range filtering:
  - Set "From" date to yesterday
  - Set "To" date to today
  - Click "Filter"
  - Verify only leads in date range are shown

### 9. CSV Export
- [ ] Click "Export CSV" button (without filters)
- [ ] Verify CSV file downloads
- [ ] Open CSV in Excel/spreadsheet application
- [ ] Verify all columns are present
- [ ] Verify data is properly formatted
- [ ] Verify UTF-8 characters display correctly
- [ ] Apply filters (form + date range)
- [ ] Click "Export CSV"
- [ ] Verify only filtered leads are in CSV
- [ ] Verify filename includes timestamp

### 10. Lead Deletion
- [ ] Click "Delete" on a single lead
- [ ] Verify confirmation dialog appears
- [ ] Confirm deletion
- [ ] Verify success message appears
- [ ] Verify lead is removed from list
- [ ] Verify nonce security (try to replay delete URL)

### 11. Bulk Delete
- [ ] Check "Select All" checkbox
- [ ] Verify all leads are checked
- [ ] Uncheck some leads manually
- [ ] Click "Delete Selected"
- [ ] Confirm deletion dialog
- [ ] Verify selected leads are deleted
- [ ] Verify success message shows count
- [ ] Verify remaining leads are intact

### 12. Pagination
- [ ] Create 25+ leads (or use database insert)
- [ ] Navigate to Leads page
- [ ] Verify only 20 leads per page
- [ ] Verify pagination controls appear
- [ ] Click "Next page" (»)
- [ ] Verify page 2 displays
- [ ] Verify different leads are shown
- [ ] Test "Previous page" («)
- [ ] Test direct page number links

### 13. Rate Limiting
- [ ] Submit same form 6 times rapidly (within 1 minute)
- [ ] Verify first 5 submissions succeed
- [ ] Verify 6th submission is blocked with error
- [ ] Wait 1 minute
- [ ] Verify submission works again

### 14. Security - Nonce Validation
- [ ] Open browser developer tools
- [ ] Submit form and capture AJAX request
- [ ] Try to replay request with invalid/old nonce
- [ ] Verify request is rejected
- [ ] Try to access admin pages without login
- [ ] Verify access is denied
- [ ] Try to delete lead without proper capability
- [ ] Verify deletion fails

### 15. Security - Input Sanitization
- [ ] Submit form with special characters in name: `<script>alert('xss')</script>`
- [ ] View lead in admin
- [ ] Verify script tags are stripped/escaped
- [ ] Submit form with SQL injection attempt in message
- [ ] Verify data is safely stored without execution
- [ ] Export CSV and verify data is properly escaped

### 16. Hooks and Filters
- [ ] Add custom code to use filter `wafbp_should_save_ip`:
```php
add_filter('wafbp_should_save_ip', function($should_save, $form_id) {
    return $form_id === 1 ? true : false;
}, 10, 2);
```
- [ ] Verify IP is saved only for form_id 1
- [ ] Add custom action hook `wafbp_after_save_lead`:
```php
add_action('wafbp_after_save_lead', function($lead_id, $lead_data) {
    error_log("Lead saved: ID {$lead_id}, Name: {$lead_data['name']}");
}, 10, 2);
```
- [ ] Submit form and verify log entry is created

### 17. Uninstall Behavior
- [ ] Ensure "Delete Data on Uninstall" is DISABLED
- [ ] Deactivate plugin
- [ ] Uninstall plugin
- [ ] Check database - verify leads table still exists
- [ ] Re-install and activate plugin
- [ ] Enable "Delete Data on Uninstall"
- [ ] Deactivate and uninstall plugin
- [ ] Check database - verify leads table is dropped

### 18. Internationalization (i18n)
- [ ] Change WordPress language to another language (if translations available)
- [ ] Verify all admin strings are translatable
- [ ] Verify opt-in label respects custom text
- [ ] Verify date formats use WordPress settings

### 19. Browser Compatibility
- [ ] Test form submission in Chrome
- [ ] Test form submission in Firefox
- [ ] Test form submission in Safari
- [ ] Test form submission in Edge
- [ ] Verify AJAX requests work in all browsers
- [ ] Test on mobile devices (iOS/Android)

### 20. Performance
- [ ] Create 1000+ leads in database
- [ ] Navigate to Leads page
- [ ] Verify page loads within reasonable time (< 3 seconds)
- [ ] Test filtering with large dataset
- [ ] Test CSV export with large dataset
- [ ] Verify no memory issues or timeouts

## Expected Results
All test cases should pass without errors. Any failures should be documented and fixed before release.

## Reporting Issues
Document any issues found with:
- Test case number
- Steps to reproduce
- Expected behavior
- Actual behavior
- Screenshots (if applicable)
- Browser/environment details
