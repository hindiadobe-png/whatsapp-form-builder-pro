# Lead Storage System Flow

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     WordPress Installation                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌────────────────────────────────────────────────────────┐    │
│  │           WhatsApp Form Builder Pro Plugin              │    │
│  │                                                          │    │
│  │  ┌────────────────┐        ┌───────────────────┐      │    │
│  │  │  Main Plugin   │───────▶│  Database Tables   │      │    │
│  │  │  Activation    │        │  - wp_wafbp_forms │      │    │
│  │  │                │        │  - wp_wafbp_leads │      │    │
│  │  └────────────────┘        └───────────────────┘      │    │
│  │                                                          │    │
│  │  ┌──────────────────────────────────────────────────┐  │    │
│  │  │           Admin Area (Backend)                    │  │    │
│  │  │                                                    │  │    │
│  │  │  ┌─────────────┐  ┌──────────────┐  ┌──────────┐│  │    │
│  │  │  │  Settings   │  │  Leads List  │  │ Forms    ││  │    │
│  │  │  │  Page       │  │  & Export    │  │ Editor   ││  │    │
│  │  │  └─────────────┘  └──────────────┘  └──────────┘│  │    │
│  │  │       │                    │               │      │  │    │
│  │  │       └────────────────────┼───────────────┘      │  │    │
│  │  │                            │                       │  │    │
│  │  │                    ┌───────▼────────┐             │  │    │
│  │  │                    │ wp_wafbp_leads │             │  │    │
│  │  │                    │     Table      │             │  │    │
│  │  │                    └────────────────┘             │  │    │
│  │  └──────────────────────────────────────────────────┘  │    │
│  │                                                          │    │
│  │  ┌──────────────────────────────────────────────────┐  │    │
│  │  │           Frontend Area (Public)                  │  │    │
│  │  │                                                    │  │    │
│  │  │  ┌─────────────────┐                              │  │    │
│  │  │  │   Shortcode     │                              │  │    │
│  │  │  │  [whatsapp...] │                              │  │    │
│  │  │  └────────┬────────┘                              │  │    │
│  │  │           │                                        │  │    │
│  │  │           ▼                                        │  │    │
│  │  │  ┌─────────────────┐                              │  │    │
│  │  │  │  Form Display   │                              │  │    │
│  │  │  │  with Opt-in    │                              │  │    │
│  │  │  └────────┬────────┘                              │  │    │
│  │  │           │                                        │  │    │
│  │  │           ▼                                        │  │    │
│  │  │  ┌─────────────────┐                              │  │    │
│  │  │  │  JavaScript     │                              │  │    │
│  │  │  │  Frontend.js    │                              │  │    │
│  │  │  └────────┬────────┘                              │  │    │
│  │  │           │                                        │  │    │
│  │  │      ┌────┴────┐                                  │  │    │
│  │  │      │         │                                  │  │    │
│  │  │      ▼         ▼                                  │  │    │
│  │  │  ┌──────┐  ┌──────────┐                          │  │    │
│  │  │  │WhatsApp │  AJAX      │                          │  │    │
│  │  │  │Link    │  │Request   │                          │  │    │
│  │  │  └──────┘  └─────┬────┘                          │  │    │
│  │  │                   │                                │  │    │
│  │  │                   └─────────────────┐             │  │    │
│  │  └─────────────────────────────────────┼─────────────┘  │    │
│  │                                          │                │    │
│  │  ┌───────────────────────────────────────┼──────────┐   │    │
│  │  │           AJAX Handler                │           │   │    │
│  │  │                                        ▼           │   │    │
│  │  │                            ┌────────────────────┐ │   │    │
│  │  │                            │  lead-handler.php  │ │   │    │
│  │  │                            │                    │ │   │    │
│  │  │                            │  1. Verify Nonce  │ │   │    │
│  │  │                            │  2. Check Opt-in  │ │   │    │
│  │  │                            │  3. Rate Limit    │ │   │    │
│  │  │                            │  4. Sanitize Data │ │   │    │
│  │  │                            │  5. Save to DB    │ │   │    │
│  │  │                            └────────┬───────────┘ │   │    │
│  │  │                                     │              │   │    │
│  │  │                                     ▼              │   │    │
│  │  │                            ┌────────────────────┐ │   │    │
│  │  │                            │ wp_wafbp_leads    │ │   │    │
│  │  │                            │      Table         │ │   │    │
│  │  │                            └────────────────────┘ │   │    │
│  │  └──────────────────────────────────────────────────┘   │    │
│  └──────────────────────────────────────────────────────────┘    │
└───────────────────────────────────────────────────────────────────┘
```

## Data Flow: Form Submission

```
User fills form → Checks opt-in → Submits form
                                      │
                        ┌─────────────┴─────────────┐
                        │                            │
                        ▼                            ▼
                  WhatsApp Link               AJAX Request
                  Opens (always)              (if opt-in)
                                                     │
                                                     ▼
                                          ┌──────────────────┐
                                          │ Security Checks  │
                                          │ - Nonce verify   │
                                          │ - Rate limit     │
                                          │ - Opt-in check   │
                                          └────────┬─────────┘
                                                   │
                                          ┌────────┴─────────┐
                                          │                  │
                                    Valid │                  │ Invalid
                                          ▼                  ▼
                                    Save Lead           Return Error
                                          │
                                          ▼
                                  ┌──────────────┐
                                  │   Database   │
                                  └──────────────┘
```

## Database Schema

```
wp_wafbp_leads
├── id (PK, AUTO_INCREMENT)
├── form_id (FK → wp_wafbp_forms.id) [INDEXED]
├── name
├── phone
├── city
├── subject
├── message
├── page_url
├── ip_address (nullable)
├── user_agent (nullable)
└── created_at [INDEXED]
```

## Security Layers

```
┌─────────────────────────────────────────────┐
│ Layer 1: Frontend Validation                │
│ - Required fields                            │
│ - Opt-in checkbox                            │
└──────────────────┬──────────────────────────┘
                   │
┌──────────────────▼──────────────────────────┐
│ Layer 2: AJAX Security                      │
│ - Nonce verification                         │
│ - Rate limiting (5/min per IP)              │
└──────────────────┬──────────────────────────┘
                   │
┌──────────────────▼──────────────────────────┐
│ Layer 3: Data Sanitization                  │
│ - sanitize_text_field()                     │
│ - sanitize_textarea_field()                 │
│ - esc_url_raw()                             │
│ - Phone: digits only                        │
│ - IP: filter_var()                          │
└──────────────────┬──────────────────────────┘
                   │
┌──────────────────▼──────────────────────────┐
│ Layer 4: Database Security                  │
│ - $wpdb->prepare()                          │
│ - Format specifiers                         │
│ - SQL injection prevention                  │
└──────────────────┬──────────────────────────┘
                   │
┌──────────────────▼──────────────────────────┐
│ Layer 5: Admin Security                     │
│ - Capability checks                         │
│ - Nonces on all actions                     │
│ - Output escaping (esc_html, esc_attr)     │
└─────────────────────────────────────────────┘
```

## Settings Integration

```
Admin → Settings Page
        │
        ├─→ save_leads (ON/OFF)
        │   └─→ Controls opt-in checkbox display
        │
        ├─→ save_ip (ON/OFF)
        │   └─→ Controls IP/User-Agent capture
        │
        ├─→ opt_in_label (text)
        │   └─→ Checkbox label shown to users
        │
        ├─→ retention_days (number)
        │   └─→ Auto-delete after X days (future)
        │
        └─→ delete_data_on_uninstall (ON/OFF)
            └─→ Remove leads table on uninstall
```

## Admin Features Flow

```
Admin → Leads Page
        │
        ├─→ Filter by Form
        ├─→ Filter by Date Range
        ├─→ View Lead Details
        ├─→ Delete Single Lead
        ├─→ Bulk Delete
        └─→ Export CSV
            │
            └─→ Generates CSV
                ├─→ UTF-8 BOM for Excel
                ├─→ All columns exported
                ├─→ Timestamp in filename
                └─→ Respects active filters
```

## Extensibility Points

```
Developers can hook into:

Filters:
├─→ wafbp_should_save_ip($bool, $form_id)
│   └─→ Override IP saving decision
│
└─→ wafbp_save_lead_payload($payload, $form_id)
    └─→ Modify lead data before save

Actions:
└─→ wafbp_after_save_lead($lead_id, $lead_data)
    └─→ Triggered after successful save
    └─→ Use cases: emails, CRM sync, webhooks
```

## File Dependencies

```
whatsapp-form-builder-pro.php (Main)
    │
    ├─→ includes/helpers.php
    ├─→ includes/admin-menu.php
    ├─→ includes/form-editor.php
    ├─→ includes/shortcode.php
    ├─→ includes/enqueue-assets.php
    ├─→ includes/style.php
    ├─→ includes/lead-db.php
    │   └─→ wafbp_create_leads_table()
    ├─→ includes/settings.php
    │   ├─→ wafbp_get_default_settings()
    │   ├─→ wafbp_get_settings()
    │   └─→ wafbp_settings_page()
    ├─→ includes/lead-handler.php
    │   ├─→ wafbp_save_lead()
    │   ├─→ wafbp_get_client_ip()
    │   ├─→ wafbp_check_rate_limit()
    │   └─→ wafbp_ajax_save_lead_handler()
    └─→ includes/admin-leads.php
        ├─→ wafbp_leads_page()
        └─→ wafbp_export_leads_csv()
```

## Testing Strategy

```
Testing Phases:

1. Unit Testing
   ├─→ Function existence
   ├─→ Input validation
   └─→ Output sanitization

2. Integration Testing
   ├─→ AJAX functionality
   ├─→ Database operations
   └─→ Settings persistence

3. Security Testing
   ├─→ Nonce verification
   ├─→ SQL injection prevention
   ├─→ XSS prevention
   └─→ CSRF protection

4. User Acceptance Testing
   ├─→ Form submission flow
   ├─→ Admin UI usability
   ├─→ CSV export accuracy
   └─→ Data integrity

5. Performance Testing
   ├─→ Large dataset handling
   ├─→ CSV export with 1000+ leads
   └─→ Query optimization
```

---

This flow diagram provides a comprehensive overview of how the lead storage system integrates with the existing WhatsApp Form Builder Pro plugin.
