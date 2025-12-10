# DoContact WordPress Plugin

**Version:** 1.0.0  
**Author:** Chamika Shashipriya  
**Text Domain:** docontact

## Description

DoContact is a WordPress plugin that enables you to collect visitor contact submissions via AJAX. It features real-time form validation, secure data storage in a custom database table, and an admin interface to view all submissions. The plugin provides a clean, user-friendly contact form with validation for email addresses and phone numbers.

## Features

- ✅ **AJAX Form Submission** - Submit contact forms without page reload
- ✅ **Real-time Validation** - Instant feedback as users type
- ✅ **Email Validation** - Server-side and client-side email format validation
- ✅ **Phone Number Validation** - Exactly 10 digits, numbers only
- ✅ **Name Validation** - Only letters and spaces allowed
- ✅ **Secure Storage** - Custom database table for storing submissions
- ✅ **Admin Dashboard** - View all submissions in WordPress admin
- ✅ **IP Address Tracking** - Automatically captures visitor IP addresses
- ✅ **Pagination** - Efficient pagination for large numbers of submissions
- ✅ **Security** - Nonce verification and input sanitization
- ✅ **Responsive Design** - Mobile-friendly form design
- ✅ **Accessibility** - ARIA labels and proper form structure

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher

## Installation

1. **Download the Plugin**
   - Download the plugin files to your computer

2. **Upload to WordPress**
   - Navigate to `wp-content/plugins/` in your WordPress installation
   - Create a folder named `Do-Contact`
   - Upload all plugin files to this folder

3. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "DoContact" in the plugin list
   - Click "Activate"

4. **Database Setup**
   - The plugin will automatically create the required database table upon activation
   - Table name: `wp_docontact_submissions` (prefix may vary based on your WordPress setup)

## Usage

### Adding the Contact Form

Use the shortcode `[docontact_form]` anywhere on your WordPress site:

```
[docontact_form]
```

**Example:**
- Add it to a page: Create a new page and insert `[docontact_form]`
- Add it to a post: Insert `[docontact_form]` in your post content
- Add it to a widget: Use a text widget and insert the shortcode

### Form Fields

The contact form includes the following fields:

- **Full Name** (Required)
  - Only letters and spaces allowed
  - Real-time validation prevents invalid characters
  
- **Email** (Required)
  - Valid email format required
  - Real-time validation feedback
  
- **Phone Number** (Required)
  - Exactly 10 digits
  - Numbers only
  - Auto-formatted as user types
  
- **Service Required** (Optional)
  - Dropdown with options:
    - General Inquiry
    - Web Development
    - SEO
    - Support
    - Other
  
- **Message** (Optional)
  - Free text area for additional comments

### Viewing Submissions

1. Navigate to **Tools → DoContact Submissions** in WordPress admin
2. View all submissions in a paginated table
3. See submission details including:
   - Submission ID
   - Full Name
   - Email (clickable mailto link)
   - Phone Number
   - Service Required
   - Message
   - IP Address
   - Submission Date/Time (UTC)

## File Structure

```
Do-Contact/
├── docontact.php                    # Main plugin file
├── README.md                        # This file
├── assets/
│   ├── css/
│   │   ├── admin.css               # Admin page styles
│   │   └── form.css                # Frontend form styles
│   └── js/
│       ├── admin.js                # Admin page JavaScript
│       └── form.js                 # Frontend form JavaScript with validation
└── includes/
    ├── class-docontact-activator.php    # Handles plugin activation & DB setup
    ├── class-docontact-db.php           # Database operations wrapper
    ├── class-docontact-validator.php   # Server-side validation logic
    ├── class-docontact-ajax.php         # AJAX request handler
    ├── class-docontact-shortcode.php    # Shortcode renderer
    └── class-docontact-admin.php        # Admin page renderer
```

## Database Schema

The plugin creates a custom table `wp_docontact_submissions` with the following structure:

| Column      | Type         | Description                    |
|-------------|--------------|--------------------------------|
| id          | BIGINT(20)   | Primary key, auto-increment    |
| full_name   | VARCHAR(191) | Submitter's full name          |
| email       | VARCHAR(191) | Submitter's email address      |
| phone       | VARCHAR(50)  | Submitter's phone number       |
| service     | VARCHAR(100) | Selected service (optional)    |
| message     | TEXT         | Message content (optional)     |
| ip_address  | VARCHAR(45)  | Submitter's IP address         |
| created_at  | DATETIME     | Submission timestamp (UTC)     |

**Indexes:**
- Primary key on `id`
- Index on `email` for faster lookups

## Security Features

- **Nonce Verification** - All AJAX requests are protected with WordPress nonces
- **Input Sanitization** - All user inputs are sanitized using WordPress functions
- **Output Escaping** - All outputs are escaped to prevent XSS attacks
- **Capability Checks** - Admin pages require `manage_options` capability
- **SQL Injection Prevention** - Uses `$wpdb->prepare()` for all database queries
- **Direct Access Protection** - All PHP files check for `ABSPATH` before execution

## Validation Rules

### Frontend (JavaScript)
- **Full Name**: Only letters and spaces, real-time filtering
- **Email**: Standard email format validation
- **Phone**: Exactly 10 digits, numbers only, maxlength restriction

### Backend (PHP)
- **Full Name**: Regex pattern `/^[A-Za-z\s]+$/`
- **Email**: PHP `filter_var()` with `FILTER_VALIDATE_EMAIL`
- **Phone**: Exactly 10 digits, numbers only

## Hooks & Filters

The plugin uses standard WordPress hooks:

- `register_activation_hook` - Creates database table on activation
- `wp_enqueue_scripts` - Registers frontend assets
- `admin_enqueue_scripts` - Registers admin assets
- `admin_menu` - Adds admin menu item
- `wp_ajax_docontact_submit` - Handles logged-in user AJAX requests
- `wp_ajax_nopriv_docontact_submit` - Handles guest user AJAX requests

## Customization

### Styling

The form styles can be customized by overriding CSS classes:

- `.docontact-wrap` - Main form container
- `.doc-button` - Submit button
- `.doc-field-error` - Field error messages
- `.doc-success` - Success messages
- `.doc-error` - Error messages

### Service Options

To modify service options, edit the `$service_options` array in:
- `includes/class-docontact-shortcode.php`
- `includes/class-docontact-ajax.php`
- `includes/class-docontact-admin.php`

## Troubleshooting

### Form Not Appearing
- Ensure the shortcode `[docontact_form]` is correctly placed
- Check that the plugin is activated
- Verify JavaScript is enabled in the browser

### Submissions Not Saving
- Check database table exists: `wp_docontact_submissions`
- Verify WordPress database user has CREATE/INSERT permissions
- Check browser console for JavaScript errors

### Validation Not Working
- Clear browser cache
- Ensure jQuery is loaded (WordPress includes it by default)
- Check browser console for JavaScript errors

### Admin Page Not Accessible
- Verify user has `manage_options` capability (Administrator role)
- Check that the plugin is activated

## Changelog

### Version 1.0.0
- Initial release
- AJAX form submission
- Real-time validation
- Admin submission viewer
- Custom database table
- IP address tracking
- Pagination support

## Support

For issues, questions, or contributions, please contact the plugin author.

## License

This plugin is provided as-is for use with WordPress.

## Credits

**Author:** Chamika Shashipriya  
**Version:** 1.0.0  
**Text Domain:** docontact

---

**Note:** This plugin follows WordPress coding standards and best practices for security, performance, and maintainability.

