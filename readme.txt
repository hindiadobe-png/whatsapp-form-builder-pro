=== WhatsApp Form Builder Pro ===
Contributors: yourname
Tags: whatsapp, contact form, shortcode, leads, crm
Requires at least: 6.4
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create customizable WhatsApp contact forms and send page context with one click.

== Description ==
This plugin creates WhatsApp contact forms with shortcode support, styling options, RTL support, and lead storage capabilities.

= Features =

* Create unlimited WhatsApp contact forms with unique shortcodes
* Fully customizable styling options (colors, fonts, borders)
* RTL language support
* Lead storage and management system
* CSV export functionality for leads
* GDPR-compliant opt-in checkbox
* Rate limiting to prevent abuse
* IP address capture (optional, disabled by default)
* Comprehensive admin dashboard for lead management

= Lead Management =

When enabled in settings, the plugin can save all form submissions to your database for follow-up:

* **Save Leads**: Toggle to enable/disable lead storage
* **Opt-in Checkbox**: Users must consent before their data is saved (GDPR compliant)
* **IP Tracking**: Optionally capture IP addresses (disabled by default for privacy)
* **Data Retention**: Set automatic deletion of leads after specified days
* **CSV Export**: Export filtered leads to CSV for use in other tools
* **Bulk Actions**: Delete multiple leads at once
* **Filtering**: Filter leads by form, date range

= Admin Usage =

1. Navigate to WhatsApp Forms → Settings
2. Enable "Save Leads" option
3. Configure opt-in label and other preferences
4. Create or edit a form
5. Place the shortcode on any page
6. View submitted leads under WhatsApp Forms → Leads
7. Export leads to CSV as needed

== Installation ==
1. Upload plugin files to /wp-content/plugins/whatsapp-form-builder-pro
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to WhatsApp Forms → Add New to create a form
4. (Optional) Go to WhatsApp Forms → Settings to enable lead storage

== Frequently Asked Questions ==

= How do I enable lead storage? =

Go to WhatsApp Forms → Settings and check the "Save Leads" option. This will add an opt-in checkbox to your forms.

= Is lead storage GDPR compliant? =

Yes, users must actively check the opt-in checkbox before their data is saved. IP address capture is disabled by default.

= Can I export my leads? =

Yes, go to WhatsApp Forms → Leads and click "Export CSV" to download all leads (or filtered results).

= What happens to leads when I uninstall the plugin? =

By default, leads are preserved. You can enable "Delete Data on Uninstall" in settings to remove all data when uninstalling.

== Changelog ==
= 2.0 =
* Security and code quality improvements
* Move inline JS/CSS to assets, sanitize inputs and outputs
* Add lead storage and management system
* Add CSV export functionality
* Add admin settings page
* Add GDPR-compliant opt-in checkbox
* Add rate limiting for form submissions
* Add extensibility hooks and filters