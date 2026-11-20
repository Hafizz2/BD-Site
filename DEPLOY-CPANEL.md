# Deploying to cPanel (main domain)

Replace `CPUSER` with your cPanel username and `yourdomain.com` with your domain everywhere below.

## Where things go

```
/home/CPUSER/
├── yebirhandana/          ← the whole project (your git repo) — NOT web-accessible
│   ├── app/  config/  storage/  vendor/  .env  …
│   └── public/            ← the only folder the web may see
└── public_html  ──►  symlink to /home/CPUSER/yebirhandana/public
```

- **Git repo:** `/home/CPUSER/yebirhandana` — outside `public_html`, so `.env` (passwords), `storage/` and `vendor/` can never be downloaded.
- **Main domain:** its document root is `public_html`, which becomes a *link* to the project's `public/` folder.

---

## 0. On your computer (once): put the project on GitHub

Create an **empty private** repository on GitHub (e.g. `yebirhandana`), then in the project folder:

```bash
git init
git add .
git commit -m "Ye Birhan Dana — first version"
git branch -M main
git remote add origin https://github.com/YOUR-GITHUB-NAME/yebirhandana.git
git push -u origin main
```

`.env`, `vendor/` and `node_modules/` are ignored automatically — passwords never go to GitHub.

## 1. cPanel settings (clicks, once)

1. **MultiPHP Manager** → set `yourdomain.com` to **PHP 8.2** (or 8.3).
2. **Select PHP Version → Extensions** (or MultiPHP INI Editor): make sure these are on — `pdo_mysql`, `mbstring`, `intl`, `gd`, `zip`, `fileinfo`, `bcmath`, `exif`, `curl`, `openssl`.
   In **MultiPHP INI Editor** set `memory_limit = 256M`, `upload_max_filesize = 16M`, `post_max_size = 20M`.
3. **MySQL® Database Wizard** → create a database, a user, give the user **ALL PRIVILEGES**.
   Write down the full names (cPanel adds a prefix, e.g. `CPUSER_ybd` / `CPUSER_ybduser`).
4. **Email Accounts** → create `info@yourdomain.com` (used to send password-reset emails).
5. **SSH Access** → enable it (or use **Terminal** inside cPanel).

## 2. Get the code (Terminal / SSH)

```bash
cd ~
git clone https://github.com/YOUR-GITHUB-NAME/yebirhandana.git yebirhandana
cd ~/yebirhandana
```

> Private repo: GitHub will ask for a password — use a **Personal Access Token** (GitHub → Settings → Developer settings → Tokens).
> Alternative: cPanel **Git™ Version Control → Create** → *Clone URL* = the GitHub URL, *Repository Path* = `/home/CPUSER/yebirhandana`.

Check the PHP version used in the terminal:

```bash
php -v
```

If it is lower than 8.2, use the full path instead of `php` in every command below:
`/opt/cpanel/ea-php82/root/usr/bin/php` (ask your host if that path doesn't exist).

## 3. Install

```bash
cd ~/yebirhandana
php -m | grep -i intl                               # must print: intl  (see "Problems we hit" below if not)
curl -sS https://getcomposer.org/installer | php    # downloads composer.phar into this folder
php composer.phar install --no-dev --optimize-autoloader
```

> Always run composer as `php composer.phar …` — not the server's own `composer` command. The server's `composer`
> can run under a different (older) PHP without the `intl` extension and fail with *"requires ext-intl"*.

## 4. Settings file (.env)

```bash
cp .env.example .env
nano .env
```

Fill in (Ctrl+O, Enter to save, Ctrl+X to exit). **`APP_ENV=production` and `APP_DEBUG=false` are essential** —
with `local`, setup tries to load fake test data and the server shows technical error pages to visitors.

```dotenv
APP_NAME="Ye Birhan Dana"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_TIMEZONE=Africa/Addis_Ababa
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=CPUSER_ybd
DB_USERNAME=CPUSER_ybduser
DB_PASSWORD=the-database-password

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=info@yourdomain.com
MAIL_PASSWORD=the-email-password
MAIL_FROM_ADDRESS=info@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

ADMIN_NAME="Super Admin"
ADMIN_EMAIL=you@example.com
ADMIN_PASSWORD=a-long-strong-password

TELEGRAM_TOKEN=            # from @BotFather (can be added later)
TELEGRAM_BOT_USERNAME=
TELEGRAM_WEBHOOK_SECRET=   # any random letters/numbers
```

Then:

```bash
php artisan key:generate
php artisan jemea:setup
```

`jemea:setup` creates the tables, roles, your Super Admin, site pages and the **sample content**
(programs, batches, a sample form, articles, events, gallery). No students, members or finance data.

## 5. Point the main domain at the site

```bash
mv ~/public_html ~/public_html_old
ln -s ~/yebirhandana/public ~/public_html
```

Then give the web server the right permissions:

```bash
chmod 711 ~
chmod 755 ~/yebirhandana ~/yebirhandana/public
find ~/yebirhandana/public -type d -exec chmod 755 {} \;
find ~/yebirhandana/public -type f -exec chmod 644 {} \;
chmod -R 775 ~/yebirhandana/storage ~/yebirhandana/bootstrap/cache
```

Check (use your real domain): `curl -s -o /dev/null -w "HTTP %{http_code}\n" https://yourdomain.com` → should print `HTTP 200`.

> If your host does not allow a linked `public_html` (you get 403 even after the permissions above), ask support to set
> the domain's document root to `/home/CPUSER/yebirhandana/public` — that does the same thing.

## 6. HTTPS

cPanel → **SSL/TLS Status** → **Run AutoSSL**. Then **Domains** → turn on **Force HTTPS Redirect**.

## 7. Cron job (makes reminders, announcements and backups run)

cPanel → **Cron Jobs** → *Once Per Minute* (`* * * * *`), command:

```bash
cd /home/CPUSER/yebirhandana && /full/path/to/php artisan schedule:run >> /dev/null 2>&1
```

Get `/full/path/to/php` by running `which php` in the terminal — cron does not always use the same PHP as your terminal.

## 8. Telegram bot (when ready)

Put `TELEGRAM_TOKEN` and `TELEGRAM_BOT_USERNAME` in `.env`, then:

```bash
cd ~/yebirhandana
php artisan jemea:setup
php artisan bot:set-webhook
```

## 9. Check

- `https://yourdomain.com` → the website
- `https://yourdomain.com/admin` → log in with `ADMIN_EMAIL` / `ADMIN_PASSWORD`, then **change the password** (top-right menu → Profile).
- Admin → Batches → open a batch → **Registration form** → **Preview form**.

---

## Every update after that

On your computer: `git add . && git commit -m "what changed" && git push`. Then on the server:

```bash
cd ~/yebirhandana
php artisan down
git pull
php composer.phar install --no-dev --optimize-autoloader
php artisan jemea:setup
php artisan up
```

`jemea:setup` is safe to run every time — it never duplicates or overwrites what you edited in the admin.

## Useful commands

| What | Command |
|---|---|
| Something looks stale after editing `.env` | `php artisan optimize:clear && php artisan jemea:setup` |
| See the last errors | `tail -n 50 storage/logs/laravel-$(date +%F).log` |
| Manual database backup | `php artisan backup:run --only-db` (saved in `storage/app/private/`) |
| Put site in maintenance | `php artisan down` / back: `php artisan up` |
| Check scheduled jobs | `php artisan schedule:list` |
| See only the error headlines | `grep -h "^\[20" storage/logs/laravel-*.log \| tail -n 5 \| cut -c1-300` |

## Problems we hit on the first deploy (and the fix)

| Symptom | Cause | Fix |
|---|---|---|
| `composer install` says *requires ext-intl … missing* | The `intl` PHP extension is off, or the server's `composer` runs under another PHP version | Turn on **intl** in cPanel → *Select PHP Version* (or ask the host for `ea-php82-php-intl`), then run composer as `php composer.phar install --no-dev -o` |
| `Call to a member function randomElement() on null` (StudentFactory) during setup | `.env` still had `APP_ENV=local`, so setup tried to load fake test data | Set `APP_ENV=production`, `APP_DEBUG=false`, then `php artisan config:clear && php artisan demo:cleanup --force && php artisan jemea:setup`, and switch the sample batches' links off in Admin → Registration forms. (Newer versions refuse to run in this state and tell you what to change.) |
| Site shows 403 after linking `public_html` | Folder permissions | The `chmod` commands in step 5 |
| `curl` shows 301 | Normal redirect (http → https, or www → no-www) | Check with `curl -sI https://yourdomain.com \| grep -i location`; the final page should be `HTTP 200` |
