# AarvixCMS 🚀

AarvixCMS is a modern, blazing-fast, and lightweight Content Management System. Built on the bleeding edge of the PHP ecosystem with **Laravel 13**, **Tailwind CSS v4**, and **Alpine.js**, it provides a stellar developer experience and a robust foundation for building content-driven web applications.

## ✨ Key Features

- **Robust Role-Based Access Control (RBAC):** Built-in permissions architecture featuring Admin, Editor, and Author roles with fine-grained access checks.
- **Dynamic Form Builder:** A powerful Alpine.js-powered drag-and-drop form builder. Generate complex forms in the admin panel and render them dynamically on the frontend.
- **Stunning Frontend UI:** Fully responsive frontend themed with the latest Tailwind CSS v4 design tokens, including automatic Dark/Light mode support.
- **Blazing Fast Caching:** Features a custom `PageCacheMiddleware` for statically caching full-page HTML responses for guests, automatically invalidated via Eloquent Model events. Zero N+1 queries by design.
- **Dynamic Page Templates:** Choose between `Default`, `Full-Width`, `Sidebar`, or `Landing` page layouts right from the dashboard.
- **Media Management:** Built-in upload handlers utilizing `Intervention Image` for thumbnail generation and WebP conversion.
- **Security First:** Strict HTMLPurifier enforcement, CSRF protection, comprehensive rate limiting, and zero lazy-loading violations.

## 🛠 Tech Stack

- **Framework:** [Laravel 13](https://laravel.com) (PHP 8.3+)
- **Styling:** [Tailwind CSS v4](https://tailwindcss.com/)
- **Interactivity:** [Alpine.js v3](https://alpinejs.dev/)
- **Authentication:** Laravel Fortify
- **Testing:** PHPUnit 12

---

## 💻 Local Development Setup

To get started contributing or building locally with standard tooling (Valet, Sail, or artisan serve):

1. **Clone the repository:**
   ```bash
   git clone https://github.com/ketandholakia/aarvixcms.git
   cd aarvixcms
   ```

2. **Install Dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Configure Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Make sure to configure your `DB_*` variables in `.env` for your local database.*

4. **Run Migrations & Seeders:**
   ```bash
   php artisan migrate --seed
   ```
   *The default admin account is `admin@example.com` / `password`.*

5. **Link Storage & Serve:**
   ```bash
   php artisan storage:link
   npm run dev
   php artisan serve
   ```

---

## 🪟 WAMP Server Deployment Guide

This CMS has been specifically optimized and tested for the Windows WAMP environment (e.g., `L:\UniWamp`). Follow these instructions to deploy the CMS locally or on a Windows-based production server.

### 1. Environment Configuration

1. Clone or copy the project into your WAMP `www` directory (e.g. `L:\UniWamp\www\aarvixcms`).
2. Copy `.env.example` to `.env` and update your WAMP MySQL credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=aarvixcms
   DB_USERNAME=root
   DB_PASSWORD=
   ```

### 2. Composer & Storage Setup

From your command line in the project root:

```bash
composer install --optimize-autoloader --no-dev
php artisan key:generate
php artisan storage:link
```

### 3. VHost Configuration (Recommended)

To access the CMS via a clean URL like `http://aarvixcms.local`:

1. Open `httpd-vhosts.conf` (e.g., `bin\apache\apacheX.Y.Z\conf\extra\`).
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
3. Add `127.0.0.1 aarvixcms.local` to your Windows `C:\Windows\System32\drivers\etc\hosts` file.
4. Restart WAMP Apache services.

### 4. Production Optimization

If exposing this environment to the internet, optimize Laravel's performance and disable debug mode (`APP_DEBUG=false`):

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 🧪 Testing

AarvixCMS ships with a comprehensive test suite covering RBAC policies, media uploads, full-page caching headers, and dynamic form builder schema parsing.

To run the test suite:
```bash
php vendor/bin/phpunit --testdox
```

## 📄 License
AarvixCMS is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
