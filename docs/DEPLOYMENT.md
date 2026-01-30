# Deployment Guide

This guide covers deploying the WordPress Starter Theme to production.

## Pre-Deployment Checklist

### 1. Environment Configuration

- [ ] `WP_DEBUG` is set to `false`
- [ ] `WP_DEBUG_LOG` is set to `true` (logs to `wp-content/debug.log`)
- [ ] `WP_DEBUG_DISPLAY` is set to `false`
- [ ] Database credentials are correct
- [ ] SSL certificate is installed and valid

### 2. Build Assets

```bash
# Install dependencies
npm install
composer install --no-dev --optimize-autoloader

# Build production assets
npm run build

# Verify build output exists
ls -la dist/
```

### 3. Theme Options

Ensure these are configured in WordPress Admin → Theme Options:

- [ ] Site logo uploaded
- [ ] Favicon uploaded
- [ ] Contact information filled
- [ ] Social media links added
- [ ] Legal pages (Privacy, Imprint) assigned
- [ ] Rybbit Analytics Plugin installed and configured

### 4. Plugin Requirements

Required plugins (install via Composer or manually):

- [ ] **ACF PRO** - Advanced Custom Fields (premium, manual install)

Recommended plugins (auto-install via `composer install`):

- [ ] Yoast SEO
- [ ] ACF Extended
- [ ] Contact Form 7
- [ ] WP Mail SMTP
- [ ] WP-Optimize
- [ ] Admin and Site Enhancements

## Server Requirements

### Minimum Requirements

| Requirement         | Version              |
| ------------------- | -------------------- |
| PHP                 | 8.4+                 |
| MySQL               | 5.7+ / MariaDB 10.3+ |
| WordPress           | 6.4+                 |
| Memory Limit        | 256M                 |
| Max Execution Time  | 60s                  |
| Upload Max Filesize | 64M                  |

### Recommended PHP Extensions

- `mbstring`
- `xml`
- `curl`
- `gd` or `imagick`
- `intl`
- `zip`

### PHP Configuration

```ini
; php.ini recommendations
memory_limit = 256M
max_execution_time = 60
post_max_size = 64M
upload_max_filesize = 64M
max_input_vars = 3000
```

## Deployment Methods

### Method 1: Git Deployment

1. Clone repository on server:

```bash
cd /path/to/wp-content/themes
git clone https://github.com/your-repo/starter.git starter
cd starter
```

2. Install dependencies:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

3. Set permissions:

```bash
chmod -R 755 .
chmod -R 775 compiled/
```

### Method 2: Manual Upload

1. Build locally:

```bash
npm install && npm run build
composer install --no-dev --optimize-autoloader
```

2. Create deployment archive (exclude dev files):

```bash
tar --exclude='node_modules' \
    --exclude='.git' \
    --exclude='tests' \
    --exclude='.github' \
    -czvf theme-deploy.tar.gz .
```

3. Upload and extract to `wp-content/themes/starter/`

### Method 3: CI/CD Pipeline

See `.github/workflows/` for automated deployment examples.

## Post-Deployment Tasks

### 1. Clear Caches

```bash
# Clear WordPress transients
wp transient delete --all

# Clear object cache (if using Redis/Memcached)
wp cache flush

# Clear Blade template cache
rm -rf compiled/*
```

### 2. Regenerate Permalinks

WordPress Admin → Settings → Permalinks → Save Changes

### 3. Verify Theme Activation

```bash
# Check active theme
wp theme status starter

# Activate if needed
wp theme activate starter
```

### 4. Test Critical Paths

- [ ] Homepage loads correctly
- [ ] Navigation works on desktop and mobile
- [ ] Contact form submits successfully
- [ ] Images load (including WebP)
- [ ] Analytics tracking fires
- [ ] SEO meta tags present
- [ ] SSL redirects work

## Performance Optimization

### Enable Caching

1. **Page Caching**: Use WP Super Cache, W3 Total Cache, or server-level caching
2. **Object Caching**: Configure Redis or Memcached
3. **CDN**: Configure Cloudflare or similar CDN

### Server Configuration

**Nginx (recommended)**:

```nginx
location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}

# Gzip compression
gzip on;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
```

**Apache (.htaccess)**:

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
</IfModule>

<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css application/javascript
</IfModule>
```

## Security Hardening

### File Permissions

```bash
# Directories
find . -type d -exec chmod 755 {} \;

# Files
find . -type f -exec chmod 644 {} \;

# Writable directories
chmod 775 compiled/
```

### wp-config.php Security

```php
// Disable file editing
define('DISALLOW_FILE_EDIT', true);

// Limit post revisions
define('WP_POST_REVISIONS', 5);

// Security keys (generate at https://api.wordpress.org/secret-key/1.1/salt/)
define('AUTH_KEY', 'your-unique-key');
// ... other keys
```

### Content Security Policy

The theme includes CSP headers. Review `src/Security.php` and adjust as needed for your environment.

## Monitoring

### Recommended Tools

- **Uptime**: UptimeRobot, Pingdom
- **Error Tracking**: Sentry, LogRocket
- **Analytics**: Rybbit (via plugin), or Plausible
- **Performance**: Google PageSpeed Insights, WebPageTest

### Log Files

Monitor these logs:

- `/wp-content/debug.log` - WordPress errors
- Server access/error logs
- PHP-FPM logs (if applicable)

## Rollback Procedure

If issues occur after deployment:

1. **Quick Rollback** (if using Git):

```bash
git checkout previous-tag
composer install --no-dev
npm run build
```

2. **Database Rollback** (if schema changes):

```bash
wp db import backup.sql
```

## Environment Variables

Set these in your hosting environment or `.env` file:

```bash
# Database
DB_NAME=wordpress
DB_USER=wp_user
DB_PASSWORD=secure_password
DB_HOST=localhost

# WordPress
WP_DEBUG=false
WP_ENVIRONMENT_TYPE=production

# Theme
VITE_BASE_URL=https://your-domain.com
```

## Troubleshooting

### Common Issues

**Blank Page / 500 Error**

- Check `wp-content/debug.log`
- Verify PHP version compatibility
- Check file permissions

**Assets Not Loading**

- Verify `npm run build` completed
- Check `dist/manifest.json` exists
- Verify file paths in templates

**Blade Cache Issues**

- Clear `compiled/` directory
- Check write permissions

**ACF Fields Missing**

- Verify ACF PRO is activated
- Check field group export in code

## Support

For issues, check:

1. [GitHub Issues](https://github.com/your-repo/starter/issues)
2. WordPress Debug Log
3. Server error logs
