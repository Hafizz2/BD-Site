# Build: Ye Birhan Dana (የብርሃን ዳና) — Jemea Management System

You are building a production-ready web system for **Ye Birhan Dana**, a Muslim jemea (student association) in Addis Ababa, Ethiopia, made up of students of EIADE. It runs comparative-religion trainings, madrasa training, charity, stage da'wah/events, trips and digital da'wah.

The system has 4 parts:
1. A **public website** (company profile + online registration)
2. A **Filament admin panel** for full management
3. **Excel import** of student data (from Google Forms exports)
4. A **Telegram bot** for finance notifications and announcements

Work **phase by phase** (see "Build phases" at the bottom). At the start, read this whole file, then show me a short plan for Phase 1 and wait for my OK. After each phase: run migrations, seeders and tests, and give me a short summary of what's done and how to try it. Ask me before any big decision that isn't covered here; don't invent business rules.

---

## 1. Tech stack

- **Laravel** (latest stable), PHP 8.3+
- **Filament** (latest stable major) for the admin panel at `/admin`
- **MySQL** (works on cPanel shared hosting); use `database` queue driver + scheduler via a single cron so it runs on cheap hosting
- **Blade + Tailwind (or plain CSS)** for the public site; I'll provide the existing HTML/CSS home page (`ye-birhan-dana-home.html`) — convert it into Blade layouts/components and keep its design exactly
- Packages (use these unless you have a strong reason, and tell me if you swap one):
  - `bezhansalleh/filament-shield` + `spatie/laravel-permission` — roles & permissions
  - `maatwebsite/excel` (or `openspout/openspout`) — .xlsx/.csv import & export
  - `nutgram/laravel` — Telegram bot (webhook mode in production, polling command for local dev)
  - `spatie/laravel-activitylog` — audit log
  - `spatie/laravel-medialibrary` (+ Filament plugin) — gallery, receipts, team photos
  - `spatie/laravel-settings` (+ Filament plugin) — site settings (contact info, socials, bank accounts, etc.)
  - `pestphp/pest` — tests
- Languages: **English + Amharic** UI for the public site (Laravel localization, `lang/en`, `lang/am`). Admin can be English only. Use `Noto Sans Ethiopic` for Amharic text.
- Timezone: `Africa/Addis_Ababa`. Currency: **ETB**.

---

## 2. Roles & permissions

| Role | Access |
|---|---|
| **Super Admin** | Everything, incl. users, roles, settings, bot config |
| **Admin / Coordinator** | Programs, batches, students, registrations, imports, announcements |
| **Finance** | Fee plans, dues, payments (approve/reject), expenses, finance reports. Read-only on students |
| **Registrar** | Registrations, students, imports. No finance |
| **Teacher** | Their own batches: sessions + attendance only |
| **Media / Content** | Public site content: pages, services, gallery, events, team, testimonials |

- Use Filament Shield to generate permissions per resource. Navigation should only show what each role can access.
- Every create/update/delete and every payment approval is logged with the user who did it (activity log, viewable by Super Admin).

---

## 3. Data model (migrations + Eloquent models + factories)

Use soft deletes on people and money tables. Use UUIDs or ULIDs for anything exposed in a URL or a Telegram deep link.

**Programs & batches**
- `programs` — name (en/am), slug, type (`college_training`, `madrasa`, `kids_training`, `charity`, `event`, `trip`, `digital_dawah`, `other`), description, cover image, is_active, show_on_website
- `batches` — program_id, name (e.g. "Batch 5"), season (`summer`, `winter`, `regular`), start_date, end_date, capacity, registration_open (bool), status (`planned`, `active`, `completed`)

**People**
- `students` — full_name, gender, date_of_birth (nullable), phone (normalized, unique-ish), alt_phone, email, address/sub_city, school/university, grade_or_year, guardian_name, guardian_phone, guardian_relation, **guardian_consent (bool) + consent_at**, photo_consent (bool), notes, status (`active`, `inactive`, `graduated`, `blocked`), source (`web_form`, `import`, `manual`), telegram_chat_id (nullable), telegram_linked_at
- `enrollments` — student_id, batch_id, enrolled_at, status (`pending`, `approved`, `rejected`, `dropped`, `completed`), certificate_issued_at — a student can be in many batches over time
- `members` (jemea volunteers/team, separate from students) — user_id nullable, full_name, phone, role/title (e.g. "Finance Head"), team (`finance`, `media`, `teaching`, `dawah`, `logistics`…), bio, photo, show_on_website, monthly_contribution_plan_id (nullable), telegram_chat_id
  - Note: some people may be both a student and a member — link with an optional `student_id` on members.

**Registrations (public form)**
- `registrations` — batch_id, all submitted fields as above, status (`pending`, `approved`, `rejected`, `waitlisted`), reviewed_by, reviewed_at, rejection_reason, student_id (set when approved and matched/created)
- On approve: find an existing student by normalized phone (or create one), create the enrollment, notify via Telegram if linked.

**Finance**
- `fee_plans` — name, amount (decimal, ETB), frequency (`one_time`, `monthly`, `per_batch`), applies_to (`batch`, `member_contribution`), batch_id nullable, due_day (for monthly), is_active
- `dues` (what someone owes) — payable (morph: student or member), fee_plan_id, period (e.g. `2026-11` or batch id), amount, due_date, status (`unpaid`, `partially_paid`, `paid`, `waived`, `overdue`), waived_by/reason
- `payments` — due_id, payable (morph), amount, method (`telebirr`, `cbe_birr`, `cbe`, `awash`, `abyssinia`, `cash`, `other`), transaction_ref, paid_at, receipt (media), status (`pending_review`, `approved`, `rejected`), submitted_via (`admin`, `telegram`, `web`), reviewed_by, reviewed_at, rejection_reason
- `expenses` — category (`printing`, `venue`, `food`, `transport`, `charity_distribution`, `media`, `other`), amount, spent_at, description, receipt (media), program_id/batch_id nullable, recorded_by
- `donations` (optional, Phase 4) — donor name (nullable/anonymous), amount, method, purpose, received_at
- Dues are generated by a scheduled job (monthly plans on the 1st; per-batch plans when an enrollment is approved). Make the generator idempotent: running it twice must not create duplicates.

**Attendance** (Phase 4)
- `class_sessions` — batch_id, title/topic, teacher (member_id), starts_at, location
- `attendances` — class_session_id, student_id, status (`present`, `absent`, `late`, `excused`)

**Telegram**
- `telegram_link_tokens` — token (random, single-use, expires 7 days), linkable (morph: student/member), used_at
- `announcements` — title, body (Markdown-safe for Telegram), image (optional), audience (`everyone`, `all_students`, `all_members`, `program`, `batch`, `team`, `unpaid`, `custom`), audience filter (json), channels (`telegram_bot`, `telegram_channel`, `website`), status (`draft`, `scheduled`, `sending`, `sent`, `failed`), scheduled_at, sent_at, created_by
- `notification_logs` — announcement_id nullable, notifiable (morph), chat_id, type (`announcement`, `due_reminder`, `payment_approved`, `payment_rejected`, `registration_update`), status (`queued`, `sent`, `failed`, `blocked_bot`), error, sent_at

**Public website content**
- `pages` (About, etc.), `services` (cards on home page: title en/am, description, icon, tag, order, featured), `events` (title, date, location, description, cover, registration link), `gallery_albums` + media, `team_members` (or reuse `members` with `show_on_website`), `testimonials`, `faqs`, `stats` (label + value for the hero counters)
- Site settings: name, tagline, Quran verse block, phone, email, address, Telegram/Facebook/YouTube/TikTok links, bank/Telebirr accounts for payments, bot username

**Imports**
- `imports` — file, original_filename, type (`students`), batch_id (target), column_mapping (json), total_rows, created_count, updated_count, skipped_count, failed_count, status, error_report_file, imported_by

---

## 4. Excel import (important, build carefully)

The data comes from **Google Forms → Google Sheets → downloaded as .xlsx or .csv**. Column headers are messy, long question texts (often in Amharic), and vary from batch to batch.

Flow, as a Filament page/wizard ("Import Students"):
1. **Upload** .xlsx / .xls / .csv (max ~10 MB) and **pick the target batch** (or "no batch").
2. **Map columns**: read the header row and show a dropdown for each system field (full_name, phone, gender, date_of_birth, school, grade, guardian_name, guardian_phone, sub_city, email, notes…) with the sheet's columns as options. **Auto-guess** mappings with fuzzy matching on English and Amharic keywords (e.g. "ስም" / "name" → full_name, "ስልክ" / "phone" → phone). Allow "ignore" and allow saving a mapping as a named **template** so the next batch is one click.
3. **Preview**: show the first ~20 rows as they will be saved, with per-row validation errors highlighted.
4. **Duplicate rule** (let the admin choose): match on normalized phone → `skip` / `update empty fields only` / `overwrite`.
5. **Run as a queued job** in chunks (e.g. 200 rows), with a progress indicator. Wrap each chunk in a transaction.
6. **Result**: counts (created / updated / skipped / failed) and a downloadable **error report .xlsx** listing failed rows with the reason.
7. Save everything to the `imports` table (import history page, with the ability to see which students came from which import).

Normalization rules:
- **Ethiopian phone numbers**: accept `09xxxxxxxx`, `07xxxxxxxx`, `9xxxxxxxx`, `+2519…`, `2519…`, with spaces or dashes → store as E.164 `+2519xxxxxxxx` / `+2517xxxxxxxx`. Invalid numbers are an error for that row, not a crash.
- Trim whitespace, collapse double spaces, title-case Latin names, leave Amharic names unchanged.
- Gender: map `ወንድ`/`male`/`M` → male, `ሴት`/`female`/`F` → female.
- Dates: handle Excel serial dates, `d/m/Y`, `m/d/Y`, `Y-m-d`. If it's clearly an Ethiopian calendar year (e.g. 2017 when the Gregorian year is 2024–2026), flag the row rather than guessing silently.
- Google Forms "Timestamp" column → `registered_at`.
- Create an `enrollment` in the chosen batch for each imported student (status `approved`).

Also add **Export** (filtered students, payments, dues, attendance) to .xlsx from Filament table bulk/header actions.

Write Pest tests for: phone normalization, header auto-mapping, the duplicate rules, Excel date parsing, and a full import of a sample file (put a realistic messy sample at `tests/fixtures/students_sample.xlsx`, with Amharic headers, duplicates and bad phones).

---

## 5. Telegram bot

Library: `nutgram/laravel`. Bot token, webhook secret, finance group chat id and public channel id go in `.env` and are editable in settings (except the token).

**Linking accounts** (people must be linked before we can message them)
- Each student/member gets a personal deep link: `https://t.me/<bot>?start=<token>`. Admin can copy it, show it as a QR code, or send it in bulk via SMS later.
- Fallback: `/start` with no token → bot asks the user to **share their contact** (Telegram contact button) → match the phone (normalized) to a student/member → link. If there's no match, tell them to contact the jemea.
- Store `telegram_chat_id`; handle the user blocking the bot (mark `blocked_bot`, stop sending).

**Commands for students/members** (Amharic + English replies, language chosen at first start)
- `/start` — welcome + link account
- `/balance` — what they owe (unpaid/overdue dues) and their recent payments
- `/pay` — shows the jemea payment accounts (from settings), then asks them to choose which due they're paying, enter the amount and transaction reference, and **send a screenshot of the receipt** → creates a `payment` with status `pending_review`
- `/announcements` — last 5 announcements
- `/mybatch` — their batch, schedule and next session (Phase 4)
- `/help`, `/language`

**Finance notifications**
- When a payment is submitted (via bot or admin) → post to the **finance team Telegram group** with the receipt image, payer, amount, due, and **inline buttons: ✅ Approve / ❌ Reject**. Only Telegram users linked to a member with the Finance role can press them; check that server-side. Rejecting asks for a reason.
- On approve/reject (from Telegram or Filament) → notify the payer and update the due status (`paid` / `partially_paid`).
- **Reminders** (scheduled, queued):
  - 3 days before the due date
  - on the due date
  - overdue: every 7 days, at most 3 times, then stop and list them on the finance dashboard
  - Never send reminders between 22:00 and 07:00 Addis time, or during Jumu'ah time on Friday (12:00–13:30). Make these windows configurable.
- Weekly finance summary to the finance group (collected this week, outstanding, number of pending receipts).

**Announcements**
- Created in Filament (rich text → converted to Telegram-safe HTML), with optional image and **audience** selection (everyone / all students / all members / a program / a batch / a team / everyone with unpaid dues / a custom selection).
- "Send test to me" button, then **Send now** or **Schedule**.
- Sending: a queued job that fans out to each recipient's `chat_id`, **rate-limited (~25 messages/sec, respect Telegram 429 `retry_after`)**, logging every message in `notification_logs`. Show delivered / failed / blocked counts on the announcement page.
- Optionally also post to the public Telegram channel and show it on the website's announcements/events section.

Provide `php artisan bot:set-webhook`, `bot:poll` (local dev), and make the bot code testable (Nutgram's testing helpers; write tests for linking, `/balance`, the `/pay` flow, and the approve-button permission check).

---

## 6. Filament admin panel

**Dashboard widgets** (shown according to role)
- Students total / active / by program; open registrations pending review
- Finance: collected this month, outstanding, overdue count, pending receipts (with a link), income vs expenses chart (last 6 months)
- Recent activity, upcoming sessions/events
- Telegram: % of students linked to the bot

**Resources**
- Programs, Batches (relation managers: enrollments, sessions, fee plans)
- Students (filters: program, batch, status, gender, linked to Telegram yes/no, has unpaid dues; actions: copy/QR Telegram link, send a Telegram message, view payment history; infolist profile page with tabs: info, enrollments, dues & payments, attendance, notifications)
- Registrations (approve / reject / waitlist; bulk approve; duplicate warning if the phone already exists)
- Members / Team
- Fee Plans, Dues (bulk waive, generate dues now), Payments (receipt preview in a modal, approve/reject with reason), Expenses, Donations
- Announcements (compose, audience preview count, test send, schedule, delivery stats)
- Imports (wizard page + history)
- Website: Pages, Services, Events, Gallery Albums, Team, Testimonials, FAQs, Stats
- Settings (site, payment accounts, bot, reminder windows)
- Users & Roles (Shield), Activity Log

Navigation groups: **People**, **Programs**, **Finance**, **Communication**, **Website**, **System**.
Brand the panel: logo `public/images/logo.png`, favicon, primary color `#0b3d2a`, accent `#c9a45c`.

**Finance reports page**: date range filter, totals by program/batch/method, list of unpaid people, export to .xlsx and PDF.

---

## 7. Public website

- Convert the provided `ye-birhan-dana-home.html` into `layouts/app.blade.php` + Blade components (`<x-nav>`, `<x-hero>`, `<x-service-card>`, `<x-footer>`, …). Keep the design, animations and mobile-first behavior exactly. Move the inline base64 images into `public/images/` (logo `pin.png`, favicons `fav64.png`, `fav180.png`).
- All content (services, stats, gallery, team, events, contact, socials) is pulled from the database/settings and is editable in admin.
- Pages: Home, About, Programs (list + detail per program showing open batches), Events, Gallery (albums), Announcements, Contact, Donate/Support (shows accounts from settings), **Register**.
- **Registration form**: choose a program and an open batch → fields → if the student is under 18 (by date of birth or a "I am under 18" toggle), guardian name, phone and **consent checkbox are required**. Photo consent checkbox. Honeypot + rate limiting (and optional Cloudflare Turnstile). After submitting: thank-you page with the bot deep link ("Get updates on Telegram").
- Language switcher EN / አማ.
- SEO: meta tags, Open Graph image, sitemap.xml, friendly slugs. Lighthouse mobile score target ≥ 90.
- **Privacy**: never show students' personal data publicly. Gallery photos of minors are only published if marked "consent confirmed" by Media.

---

## 8. Quality & security

- Form Requests / Filament validation everywhere; money stored as `decimal(12,2)`; no floats.
- Policies for every model; Shield permissions checked in Filament and in bot callback handlers.
- Telegram webhook protected with a secret token header; ignore updates from unknown chats for admin actions.
- Receipt and ID images stored on a **private** disk, served through signed URLs only to authorized roles.
- Queued jobs are retry-safe (idempotent), with failed jobs visible to Super Admin.
- Seeders: roles, a Super Admin from `.env` (`ADMIN_EMAIL`, `ADMIN_PASSWORD`), demo programs/batches/services, ~50 fake students, fee plans and payments (demo data only in local env).
- Pest feature tests for the critical flows listed above; `php artisan test` must pass at the end of each phase.
- `README.md` with: local setup, `.env` keys, how to create the bot with BotFather, set the webhook, the cron line, queue worker setup on cPanel (`schedule:run` every minute + `queue:work --stop-when-empty` via the scheduler), and backups (`spatie/laravel-backup`, daily DB backup).

---

## 9. Build phases

**Phase 1 — Foundation**
Laravel + Filament install, branding, auth, roles/permissions (Shield), settings, activity log, base models: programs, batches, students, enrollments, members. Seeders. Dashboard skeleton.

**Phase 2 — Students & import**
Registrations (public form + admin review), the full Excel import wizard with mapping templates, preview, duplicate rules, queued processing, error report, import history; Excel exports. Tests.

**Phase 3 — Finance + Telegram bot**
Fee plans, dues generator, payments, expenses, finance dashboard and reports. Bot: linking, `/balance`, `/pay` with receipt upload, finance-group approve/reject buttons, reminders with quiet hours. Tests.

**Phase 4 — Announcements + attendance**
Announcement composer, audiences, scheduling, rate-limited sending, delivery logs, channel posting. Class sessions, attendance (mobile-friendly page for teachers), certificates (PDF).

**Phase 5 — Public website**
Convert the HTML into Blade, connect all content to the DB, EN/AM localization, gallery, events, donate page, SEO, performance pass.

**Phase 6 — Hardening & deploy**
Security review, backups, README/deployment guide for cPanel or a VPS, final test run, demo data cleanup command.

Start with Phase 1. Show me the plan first.
