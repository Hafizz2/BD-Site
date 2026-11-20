# Ye Birhan Dana (የብርሃን ዳና) — Jemea Management System

Public website (EN / አማ) + Filament admin panel + Excel student import + Telegram bot.
Laravel 12, Filament 4, PHP 8.2+, MySQL/MariaDB.

- Website: `/`  ·  Admin panel: `/admin`

## How the jemea flow maps to the system

1. **Registration is by link only.** Each batch has its own link (Admin → Batches → *Registration link*). It works only while that batch's *Registration link open* is on. Share it on Telegram, the website, or send it to people individually. `/register` on its own just shows a "closed" message.
   Each batch has its own **registration form** (Admin → Batches → edit → *Registration form*): rename any field (EN/AM), make it required or optional, hide it, reorder, or add custom questions (short answer, paragraph, choice, checkboxes, yes/no, number, date). Name and phone are always asked. The *Form header* adds a cover image, title, opening note and thank-you message; *Title & description* blocks split the form into parts; *Preview form* shows it before you share. Guardian fields and consent boxes are **off by default** — switch them on for a batch that needs them. *Copy form from another batch* reuses a previous form.
2. **Registrations.** Admin → Registrations lists everyone, with tabs: *All · New · Interview · Accepted · Waiting list · Not accepted*. Each row has two buttons: **Call** (tap the number, then save what happened) and **Update status**. Choosing *Accepted* makes them a student of the batch. The same list appears on each batch's page. *Download Excel* gives the list as shown, with one column per question.
3. **Student → member.** After the course, if they agree to join, use *Make member* on the student and pick a team.
4. **Contributors.** Every member is listed in Members; only members with a *monthly contribution* plan are contributors and get monthly dues (see the *Contributors* tab).
5. **Telegram bot = promotion & notifications only:** due reminders, program invitations and announcements. Payments are recorded by the finance team in Admin → Payments.

## Local setup (XAMPP)

```bash
composer install
cp .env.example .env          # then fill in DB_*, ADMIN_EMAIL, ADMIN_PASSWORD
php artisan key:generate
php artisan jemea:setup       # migrate + seed + storage link + assets (safe to re-run)
```

Log in at `/admin` with `ADMIN_EMAIL` / `ADMIN_PASSWORD`.
**What gets seeded**

- Everywhere (also the live server): roles, the Super Admin from `.env`, site pages/settings, and **sample website content** — programs, batches (closed), a sample registration form, articles, events, gallery albums. **No people** — members, students and registrations are confidential and never shown on the website.
- Only on a local machine (`APP_ENV=local`): fake students, registrations, members and finance data, for trying things out. Remove them with `php artisan demo:cleanup` (`--all-content` also removes the sample content).

Run the tests: `php artisan test`

## `.env` keys

| Key | Purpose |
|---|---|
| `APP_URL` | Full site URL (used for links, sitemap, Telegram webhook) |
| `DB_*` | Database connection |
| `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` | First Super Admin, created by the seeder |
| `TELEGRAM_TOKEN` | Bot token from BotFather (without it, Telegram messages are skipped) |
| `TELEGRAM_BOT_USERNAME` | Bot username, used for `t.me/<bot>?start=…` links |
| `TELEGRAM_WEBHOOK_SECRET` | Random string (A–Z a–z 0–9 _ -) checked on every webhook call |
| `TELEGRAM_FINANCE_GROUP_ID`, `TELEGRAM_CHANNEL_ID` | Defaults; editable later in Admin → Settings → Bot |
| `TURNSTILE_SECRET_KEY` | Optional Cloudflare Turnstile on the registration form |
| `BACKUP_ARCHIVE_PASSWORD` | Optional password for backup zips |

## Roles

Five roles (created by `RolesSeeder`):

- **Super Admin** — everything
- **Admin** — registrations, students, batches, members, announcements
- **Finance** — money only
- **Registrar** — registrations and students only
- **Media** — the website only

Assign roles to users in Admin → System → Users. Fine-tune permissions in Admin → System → Roles.
Only active users with at least one role can log in to the panel.

## Telegram bot

1. In Telegram, talk to **@BotFather** → `/newbot` → copy the token into `TELEGRAM_TOKEN` and the username into `TELEGRAM_BOT_USERNAME`.
2. Add the bot to the finance team group, send a message there, and put the group chat id in Admin → Settings → Bot.
3. To post announcements to a channel, add the bot as a channel admin and set the channel id.
4. Production (HTTPS required): `php artisan bot:set-webhook`
   Local development (no public URL): `php artisan bot:poll`

## Cron (cPanel or VPS)

One cron line runs everything — the scheduler also works through the queue in short bursts, so no long-running worker is needed on shared hosting:

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled jobs: queue worker (`queue:work --stop-when-empty`), scheduled announcements, monthly/daily dues generation, due reminders (respecting quiet hours), weekly finance summary, daily DB backup, log/failed-job pruning.

## Deploying to cPanel

**Full step-by-step guide (folders, every command, cron, HTTPS, updates): [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md).** Short version:

1. Upload the project **outside** `public_html` (e.g. `~/yebirhandana`), and point the domain's document root to `~/yebirhandana/public` (or copy `public/` into `public_html` and fix the paths in `index.php`).
2. Create the MySQL database and user in cPanel, fill in `.env` with `APP_ENV=production`, `APP_DEBUG=false` and the real `APP_URL`.
3. Via SSH / Terminal:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan jemea:setup
   ```
4. Add the cron line above, then run `php artisan bot:set-webhook`.

After each update: `git pull && php composer.phar install --no-dev -o && php artisan jemea:setup` (it never duplicates or overwrites content you edited).

## Backups

`spatie/laravel-backup` runs a daily database backup at 02:30 (Addis time) and cleans old ones at 02:00. Backups go to `storage/app/private/<APP_NAME>`.
Run one manually with `php artisan backup:run --only-db`. Download copies off the server regularly.
On Windows/XAMPP, `mysqldump` must be on the PATH (or set `dump.dump_binary_path` in `config/database.php`).
