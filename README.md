# AarvixCMS 

AarvixCMS is a modern, lightweight Content Management System built on Laravel 13, Tailwind CSS v4, and Alpine.js. It comes with full RBAC, caching, and a dynamic form builder right out of the box.

## WAMP Server Deployment Guide

This CMS has been specifically optimized for the WAMP environment (`L:\UniWamp`). Follow these instructions to deploy the CMS locally or on a Windows-based production server.

### Prerequisites

Ensure you have the following installed and running in your WAMP control panel:
- **PHP 8.3+** (Ensure OpenSSL and PDO/MySQL extensions are enabled in your `php.ini`)
- **MySQL 8** (or higher)
- **Node.js** (for frontend asset compilation, if modifying theme)

### 1. Environment Configuration

1. Clone or copy the project into your WAMP `www` directory (e.g. `L:\UniWamp\www\aarvixcms`).
2. Copy the `.env.example` file to `.env`:
   ```bash
   copy .env.example .env
   ```
3. Update the `.env` file to match your WAMP MySQL credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=aarvixcms
   DB_USERNAME=root
   DB_PASSWORD=
   ```

### 2. Composer & NPM Setup

Run the following commands from the root directory (`L:\UniWamp\www\aarvixcms`) using the command line:

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Generate application key
php artisan key:generate

# Link the storage directory (Important for Media uploads!)
php artisan storage:link

# Install frontend dependencies and compile assets (optional if assets are already bundled)
npm install
npm run build
```

### 3. Database Migration and Seeding

You must run the migrations to create the tables. Additionally, run the seeders to create your initial Roles, Permissions, Default Settings, and Admin User.

```bash
php artisan migrate --seed
```
*Note: The default admin user credentials are `admin@example.com` / `password`. Make sure to change this immediately after logging in.*

### 4. VHost Configuration (Optional but Recommended)

For the best experience, configure a Virtual Host in WAMP so you can access the CMS via a clean URL like `http://aarvixcms.local` instead of `http://localhost/aarvixcms/public`.

1. Open `httpd-vhosts.conf` (usually located in `bin\apache\apacheX.Y.Z\conf\extra\`).
2. Add the following block:
```apache
<VirtualHost *:80>
    ServerName aarvixcms.local
    DocumentRoot "L:/UniWamp/www/aarvixcms/public"
    <Directory "L:/UniWamp/www/aarvixcms/public">
        Options +Indexes +Includes +FollowSymLinks +MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
3. Add `127.0.0.1 aarvixcms.local` to your Windows `hosts` file (`C:\Windows\System32\drivers\etc\hosts`).
4. Restart WAMP Apache services.

### 5. Production Optimization

If this WAMP environment is exposed to the public internet, you should run these caching commands to optimize Laravel's performance:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

**Security Note:** Make sure `APP_DEBUG=false` in your `.env` file!

### Admin Dashboard

Navigate to your application URL and add `/login` (e.g., `http://aarvixcms.local/login`) to access the admin panel. 

---
Developed using Laravel 13 & Tailwind v4.
