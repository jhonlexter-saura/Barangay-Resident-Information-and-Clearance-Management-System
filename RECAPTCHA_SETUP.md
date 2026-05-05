# Google reCAPTCHA Setup Guide

## Overview
This system now includes Google reCAPTCHA v2 verification as a security gate before users can access the main portal selection page.

## Setup Instructions

### 1. Get reCAPTCHA Keys
1. Go to [Google reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin)
2. Click "Create" to register a new site
3. Choose reCAPTCHA v2 ("I'm not a robot" Checkbox)
4. Add your domain(s):
   - For local development: `localhost`
   - For production: your actual domain
5. Accept the terms and click "Submit"

### 2. Configure Keys in config.php
Edit `config.php` and replace the placeholder values:

```php
$recaptcha_site_key   = "your_actual_site_key_here";
$recaptcha_secret_key = "your_actual_secret_key_here";
```

### 3. Access Flow
- `index.html` → Redirects to `captcha-gate.php`
- `captcha-gate.php` → reCAPTCHA verification gate
- `portal-selection.php` → Main portal selection (requires CAPTCHA verification)

## Security Features
- Session-based verification prevents bypassing the CAPTCHA
- Server-side verification ensures authenticity
- Automatic redirect after successful verification
- Error handling for failed verifications

## Testing
1. Clear your browser cookies/sessions
2. Visit `index.html` or `captcha-gate.php` directly
3. Complete the CAPTCHA
4. You should be redirected to the portal selection page
5. The verification is remembered for the session

## Troubleshooting
- **CAPTCHA not loading**: Check your site key in config.php
- **Verification failing**: Ensure your secret key is correct and cURL is enabled
- **Access denied**: Clear session and try again

## Production Notes
- Use different keys for development and production
- Monitor reCAPTCHA usage in Google Admin Console
- Consider implementing rate limiting for additional security