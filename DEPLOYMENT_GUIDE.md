# Deployment Guide - Loyalty System

**Complete Production Deployment Instructions**

**Version:** 1.0.0
**Date:** November 29, 2025

---

## Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Server Requirements](#server-requirements)
3. [Backend Deployment (Laravel)](#backend-deployment-laravel)
4. [Database Setup](#database-setup)
5. [Third-Party Service Configuration](#third-party-service-configuration)
6. [Mobile App Deployment](#mobile-app-deployment)
7. [Post-Deployment Configuration](#post-deployment-configuration)
8. [Security Hardening](#security-hardening)
9. [Monitoring & Maintenance](#monitoring--maintenance)
10. [Troubleshooting](#troubleshooting)

---

## Pre-Deployment Checklist

### Required Accounts

- [ ] Domain name registered
- [ ] SSL certificate (Let's Encrypt or commercial)
- [ ] Server/VPS (AWS, DigitalOcean, Linode, etc.)
- [ ] Stripe account (verified)
- [ ] Firebase account with project created
- [ ] Twilio account with verified phone number
- [ ] SendGrid account with verified sender
- [ ] Apple Developer Account (for iOS)
- [ ] Google Play Console Account (for Android)

### Required Information

- [ ] Database credentials
- [ ] SMTP server details
- [ ] All API keys collected
- [ ] Domain DNS configured
- [ ] Webhook URLs planned

---

## Server Requirements

### Minimum Specifications

**Production Server:**
- **CPU:** 2 cores minimum (4 cores recommended)
- **RAM:** 4GB minimum (8GB recommended)
- **Storage:** 40GB SSD minimum
- **OS:** Ubuntu 22.04 LTS (recommended) or Ubuntu 20.04 LTS
- **Network:** Static IP address

### Software Stack

**Required Software:**
- PHP 8.2 or higher
- Composer 2.x
- MySQL 8.0 or MariaDB 10.6+
- Nginx or Apache 2.4+
- Redis (for caching and queues)
- Node.js 18+ and npm (for asset compilation)
- Git
- Supervisor (for queue workers)
- Certbot (for SSL)

---

## Backend Deployment (Laravel)

### Step 1: Server Setup

#### 1.1 Update System

```bash
sudo apt update && sudo apt upgrade -y
```

#### 1.2 Install Required Packages

```bash
# Install PHP 8.2 and extensions
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-mysql \
  php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip \
  php8.2-gd php8.2-bcmath php8.2-redis php8.2-intl

# Install MySQL
sudo apt install -y mysql-server

# Install Redis
sudo apt install -y redis-server

# Install Nginx
sudo apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Supervisor
sudo apt install -y supervisor

# Install Git
sudo apt install -y git
```

### Step 2: Clone Repository

```bash
# Create project directory
sudo mkdir -p /var/www/loyalty-system
sudo chown -R $USER:$USER /var/www/loyalty-system

# Clone repository
cd /var/www
git clone https://github.com/your-repo/loyalty-system.git
cd loyalty-system
```

### Step 3: Install Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies (if using Vite/Mix)
npm install
npm run build
```

### Step 4: Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

#### 4.1 Edit `.env` File

```bash
nano .env
```

**Required Configuration:**

```env
# Application
APP_NAME="Loyalty System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=loyalty_system
DB_USERNAME=loyalty_user
DB_PASSWORD=your_secure_password_here

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Stripe
STRIPE_KEY=pk_live_51ABC...
STRIPE_SECRET=sk_live_51ABC...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_STARTER_PRICE_ID=price_1ABC...
STRIPE_PROFESSIONAL_PRICE_ID=price_1DEF...
STRIPE_ENTERPRISE_PRICE_ID=price_1GHI...

# Firebase
FIREBASE_PROJECT_ID=loyalty-system-xxxxx
FIREBASE_SERVER_KEY=AAAA...
FIREBASE_DATABASE_URL=https://loyalty-system-xxxxx.firebaseio.com
FIREBASE_CREDENTIALS=/var/www/loyalty-system/storage/app/firebase-credentials.json

# Twilio
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_FROM_NUMBER=+14155551234
TWILIO_VERIFY_SID=VAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# SendGrid
SENDGRID_API_KEY=SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
SENDGRID_FROM_EMAIL=noreply@yourdomain.com
SENDGRID_FROM_NAME=Loyalty System

# Mail (Laravel Mailer - uses SendGrid)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=${SENDGRID_API_KEY}
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=${SENDGRID_FROM_EMAIL}
MAIL_FROM_NAME="${APP_NAME}"
```

### Step 5: Set Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/loyalty-system

# Set directory permissions
sudo find /var/www/loyalty-system -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/loyalty-system -type f -exec chmod 644 {} \;

# Set storage and cache permissions
sudo chmod -R 775 /var/www/loyalty-system/storage
sudo chmod -R 775 /var/www/loyalty-system/bootstrap/cache
```

---

## Database Setup

### Step 1: Secure MySQL Installation

```bash
sudo mysql_secure_installation
```

### Step 2: Create Database and User

```bash
sudo mysql -u root -p
```

```sql
-- Create database
CREATE DATABASE loyalty_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'loyalty_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';

-- Grant privileges
GRANT ALL PRIVILEGES ON loyalty_system.* TO 'loyalty_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Exit
EXIT;
```

### Step 3: Run Migrations

```bash
cd /var/www/loyalty-system

# Run migrations
php artisan migrate --force

# Seed database (optional - creates sample data)
php artisan db:seed
```

### Step 4: Create Admin User

```bash
# Create first Super Admin user
php artisan tinker
```

```php
\App\Models\Admin::create([
    'name' => 'Super Admin',
    'email' => 'admin@yourdomain.com',
    'password' => bcrypt('your_secure_password'),
    'is_super_admin' => true,
]);
```

### Step 5: Optimize Application

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

---

## Third-Party Service Configuration

### Stripe Setup

#### 1. Create Products and Prices

1. Go to [Stripe Dashboard](https://dashboard.stripe.com)
2. Navigate to **Products**
3. Create 3 products:

**Starter Plan:**
- Name: Starter Plan
- Price: $29/month
- Copy Price ID → Add to `.env` as `STRIPE_STARTER_PRICE_ID`

**Professional Plan:**
- Name: Professional Plan
- Price: $99/month
- Copy Price ID → Add to `.env` as `STRIPE_PROFESSIONAL_PRICE_ID`

**Enterprise Plan:**
- Name: Enterprise Plan
- Price: $299/month
- Copy Price ID → Add to `.env` as `STRIPE_ENTERPRISE_PRICE_ID`

#### 2. Set Up Webhook

1. Navigate to **Developers → Webhooks**
2. Click **Add endpoint**
3. Endpoint URL: `https://yourdomain.com/stripe/webhook`
4. Select events:
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `customer.subscription.trial_will_end`
5. Copy **Webhook signing secret**
6. Add to `.env` as `STRIPE_WEBHOOK_SECRET`

#### 3. Add Route for Webhook

Add to `routes/api.php`:

```php
use App\Http\Controllers\StripeWebhookController;

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);
```

Exclude from CSRF protection in `app/Http/Middleware/VerifyCsrfToken.php`:

```php
protected $except = [
    'stripe/webhook',
];
```

### Firebase Setup

#### 1. Download Service Account JSON

1. Go to [Firebase Console](https://console.firebase.google.com)
2. Select your project
3. Navigate to **Project Settings → Service Accounts**
4. Click **Generate new private key**
5. Download JSON file

#### 2. Upload to Server

```bash
# Upload firebase-credentials.json to server
scp firebase-credentials.json user@yourserver:/var/www/loyalty-system/storage/app/

# Set permissions
sudo chown www-data:www-data /var/www/loyalty-system/storage/app/firebase-credentials.json
sudo chmod 600 /var/www/loyalty-system/storage/app/firebase-credentials.json
```

#### 3. Configure Mobile App

Update `mobile-app/app.config.js`:

```javascript
extra: {
  apiUrl: 'https://yourdomain.com/api',
}
```

Add Firebase config files:
- `mobile-app/android/app/google-services.json` (Android)
- `mobile-app/ios/GoogleService-Info.plist` (iOS)

### Twilio Setup

1. Go to [Twilio Console](https://console.twilio.com)
2. Copy **Account SID** → Add to `.env`
3. Copy **Auth Token** → Add to `.env`
4. Purchase phone number → Add to `.env` as `TWILIO_FROM_NUMBER`
5. Create Verify Service:
   - Navigate to **Verify → Services**
   - Create service
   - Copy **Service SID** → Add to `.env` as `TWILIO_VERIFY_SID`

### SendGrid Setup

1. Go to [SendGrid Dashboard](https://app.sendgrid.com)
2. Navigate to **Settings → API Keys**
3. Create API key with **Full Access**
4. Copy API key → Add to `.env` as `SENDGRID_API_KEY`
5. Verify sender identity:
   - Navigate to **Settings → Sender Authentication**
   - Verify email or domain
   - Add verified email to `.env` as `SENDGRID_FROM_EMAIL`

---

## Mobile App Deployment

### iOS Deployment

#### 1. Prerequisites

- Mac computer with Xcode installed
- Apple Developer Account ($99/year)
- Provisioning profiles and certificates

#### 2. Build iOS App

```bash
cd mobile-app

# Install dependencies
npm install

# iOS-specific setup
cd ios
pod install
cd ..

# Build for production
eas build --platform ios --profile production
```

#### 3. Submit to App Store

```bash
# Submit to App Store
eas submit --platform ios
```

### Android Deployment

#### 1. Build Android App

```bash
cd mobile-app

# Build for production
eas build --platform android --profile production
```

#### 2. Submit to Google Play

```bash
# Submit to Google Play
eas submit --platform android
```

### Alternative: Expo Go Testing

For testing without full deployment:

```bash
# Start Expo server
cd mobile-app
expo start

# Use Expo Go app on phone to scan QR code
```

---

## Post-Deployment Configuration

### Configure Nginx

Create Nginx configuration:

```bash
sudo nano /etc/nginx/sites-available/loyalty-system
```

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/loyalty-system/public;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Logging
    access_log /var/log/nginx/loyalty-system-access.log;
    error_log /var/log/nginx/loyalty-system-error.log;

    # Max upload size
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
}
```

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/loyalty-system /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Configure SSL with Let's Encrypt

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal (already configured by Certbot)
sudo certbot renew --dry-run
```

### Configure Queue Workers

Create Supervisor configuration:

```bash
sudo nano /etc/supervisor/conf.d/loyalty-queue.conf
```

```ini
[program:loyalty-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/loyalty-system/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/loyalty-system/storage/logs/worker.log
stopwaitsecs=3600
```

Start queue workers:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start loyalty-queue-worker:*
```

### Configure Scheduled Tasks (Cron)

```bash
sudo crontab -e -u www-data
```

Add:

```
* * * * * cd /var/www/loyalty-system && php artisan schedule:run >> /dev/null 2>&1
```

---

## Security Hardening

### 1. Firewall Configuration

```bash
# Enable UFW
sudo ufw enable

# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Check status
sudo ufw status
```

### 2. Disable Debug Mode

Ensure in `.env`:

```env
APP_DEBUG=false
```

### 3. Secure File Permissions

```bash
# Remove write permissions from sensitive files
sudo chmod 644 /var/www/loyalty-system/.env
sudo chmod 644 /var/www/loyalty-system/config/*.php
```

### 4. Configure PHP Security

Edit `/etc/php/8.2/fpm/php.ini`:

```ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.2-fpm
```

### 5. Enable CORS (if needed)

If mobile app is on different domain, configure CORS in `config/cors.php`:

```php
'paths' => ['api/*'],
'allowed_origins' => ['https://yourdomain.com'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => false,
```

---

## Monitoring & Maintenance

### Log Monitoring

```bash
# Laravel logs
tail -f /var/www/loyalty-system/storage/logs/laravel.log

# Nginx access logs
tail -f /var/log/nginx/loyalty-system-access.log

# Nginx error logs
tail -f /var/log/nginx/loyalty-system-error.log

# Queue worker logs
tail -f /var/www/loyalty-system/storage/logs/worker.log
```

### Database Backups

Create backup script:

```bash
sudo nano /usr/local/bin/backup-loyalty-db.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/loyalty-system"
DATE=$(date +%Y%m%d_%H%M%S)
FILENAME="loyalty_system_$DATE.sql.gz"

mkdir -p $BACKUP_DIR
mysqldump -u loyalty_user -p'your_password' loyalty_system | gzip > $BACKUP_DIR/$FILENAME

# Keep only last 7 days of backups
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete

echo "Backup completed: $FILENAME"
```

Make executable and schedule:

```bash
sudo chmod +x /usr/local/bin/backup-loyalty-db.sh

# Add to crontab (daily at 2 AM)
sudo crontab -e
0 2 * * * /usr/local/bin/backup-loyalty-db.sh
```

### Performance Monitoring

Install and configure Laravel Telescope (development only) or use external monitoring:

- **New Relic** - Application performance monitoring
- **Sentry** - Error tracking
- **Datadog** - Infrastructure monitoring
- **Uptime Robot** - Uptime monitoring

---

## Troubleshooting

### Common Issues

#### 1. 500 Internal Server Error

**Check:**
- Laravel logs: `storage/logs/laravel.log`
- Nginx error logs: `/var/log/nginx/loyalty-system-error.log`
- PHP-FPM logs: `/var/log/php8.2-fpm.log`

**Fix:**
```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 2. Database Connection Failed

**Check:**
- Database credentials in `.env`
- MySQL service status: `sudo systemctl status mysql`
- User permissions in MySQL

**Fix:**
```bash
# Test connection
php artisan tinker
DB::connection()->getPdo();
```

#### 3. Queue Not Processing

**Check:**
```bash
# Check Supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart loyalty-queue-worker:*
```

#### 4. Stripe Webhooks Not Working

**Check:**
- Webhook URL is accessible
- CSRF exception added
- Webhook signing secret correct
- Check Stripe webhook logs

**Test:**
```bash
# Send test webhook from Stripe Dashboard
# Check Laravel logs for errors
```

#### 5. Mobile App Can't Connect to API

**Check:**
- API URL in `app.config.js`
- CORS configuration
- SSL certificate valid
- Firewall rules

**Test:**
```bash
# Test API from mobile device browser
https://yourdomain.com/api/health
```

---

## Deployment Checklist

### Pre-Launch

- [ ] All environment variables configured
- [ ] Database migrations run successfully
- [ ] Super Admin user created
- [ ] SSL certificate installed and working
- [ ] Stripe products and webhooks configured
- [ ] Firebase credentials uploaded
- [ ] Twilio Verify service created
- [ ] SendGrid sender verified
- [ ] Queue workers running
- [ ] Cron jobs configured
- [ ] File permissions set correctly
- [ ] Firewall configured
- [ ] Debug mode disabled
- [ ] Backups configured

### Post-Launch

- [ ] Test user registration (OTP)
- [ ] Test Stripe subscription creation
- [ ] Test push notifications
- [ ] Test SMS/OTP flow
- [ ] Test email sending
- [ ] Test mobile app authentication
- [ ] Test QR code generation
- [ ] Test reward redemption
- [ ] Monitor error logs for 24 hours
- [ ] Set up uptime monitoring
- [ ] Configure error alerting

---

## Quick Reference Commands

### Laravel Artisan

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan optimize

# Run migrations
php artisan migrate --force

# Seed database
php artisan db:seed

# Create user in Tinker
php artisan tinker

# Check queue status
php artisan queue:work --once

# List all routes
php artisan route:list
```

### System Management

```bash
# Restart Nginx
sudo systemctl restart nginx

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Restart MySQL
sudo systemctl restart mysql

# Restart Redis
sudo systemctl restart redis

# Restart Queue Workers
sudo supervisorctl restart loyalty-queue-worker:*

# Check all service status
sudo systemctl status nginx php8.2-fpm mysql redis
```

---

## Support & Resources

### Documentation Links
- Laravel: https://laravel.com/docs
- Filament: https://filamentphp.com/docs
- Stripe: https://stripe.com/docs
- Firebase: https://firebase.google.com/docs
- Twilio: https://www.twilio.com/docs
- SendGrid: https://docs.sendgrid.com

### Getting Help
- GitHub Issues: [Your Repository]
- Email Support: support@yourdomain.com
- Documentation: https://docs.yourdomain.com

---

## Conclusion

Your Loyalty System is now fully deployed and ready for production use!

**Next Steps:**
1. Test all functionality thoroughly
2. Monitor logs for the first 24-48 hours
3. Set up automated monitoring and alerts
4. Begin onboarding first merchants
5. Gather user feedback
6. Plan Phase 2 features

---

**Deployment Guide Version:** 1.0.0
**Last Updated:** November 29, 2025
**Status:** Production Ready ✅

**Congratulations on completing Phase 1 MVP!** 🎉
