<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * One command for first install AND every later update:
 *
 *   php artisan jemea:setup
 *
 * Runs migrations, seeds roles + Super Admin + site content + sample website content
 * (fake students/finance only on a local machine), links storage, publishes admin assets
 * and caches config/routes/views. Safe to run again — nothing is duplicated or overwritten.
 */
class SetupSite extends Command
{
    protected $signature = 'jemea:setup {--no-seed : Only migrate and refresh caches (skip seeders)}';

    protected $description = 'Install or update the site: migrate, seed (roles, admin, sample content), link storage, cache';

    public function handle(): int
    {
        $production = app()->environment('production');

        $this->components->info('Setting up Ye Birhan Dana ('.app()->environment().')');

        // A server installed with `composer install --no-dev` has no dev tools — it must not run as "local".
        if (! $production && ! class_exists(\Faker\Factory::class)) {
            $this->components->error('APP_ENV is "'.app()->environment().'", but this looks like a server (dev packages are not installed).');
            $this->line('  Open .env and set:  APP_ENV=production  and  APP_DEBUG=false');
            $this->line('  Then run:           php artisan config:clear && php artisan jemea:setup');

            return self::FAILURE;
        }

        if (blank(config('app.key'))) {
            $this->components->error('APP_KEY is empty. Run: php artisan key:generate');

            return self::FAILURE;
        }

        $this->components->task('Database migrations', fn () => $this->callSilently('migrate', ['--force' => true]) === 0);

        if (! $this->option('no-seed')) {
            if (blank(config('app.admin.email')) || blank(config('app.admin.password'))) {
                $this->components->warn('ADMIN_EMAIL / ADMIN_PASSWORD are not set in .env — no Super Admin will be created.');
            }
            $this->components->task(
                $production ? 'Roles, Super Admin, site content, sample website content' : 'Roles, Super Admin, site content, sample content + demo students/finance',
                fn () => $this->callSilently('db:seed', ['--force' => true]) === 0
            );
        }

        $this->components->task('Public storage link', function () {
            if (File::exists(public_path('storage'))) {
                return true;
            }

            return $this->callSilently('storage:link') === 0;
        });

        $this->components->task('Website images on the public disk', fn () => $this->callSilently('media:make-website-public') === 0);

        $this->components->task('Admin panel assets', fn () => $this->callSilently('filament:assets') === 0);

        $this->callSilently('optimize:clear');
        if ($production) {
            $this->components->task('Caching config, routes, views', fn () => $this->callSilently('optimize') === 0);
            $this->components->task('Caching admin components & icons', fn () => $this->callSilently('filament:optimize') === 0);
        }

        $this->newLine();
        $this->components->bulletList(array_filter([
            'Admin panel: '.rtrim(config('app.url'), '/').'/admin',
            blank(config('nutgram.token')) ? 'Telegram bot: not set up yet (add TELEGRAM_TOKEN to .env, then run php artisan bot:set-webhook)' : null,
            ! $production ? 'Local machine: demo students & finance are loaded. Remove them with php artisan demo:cleanup' : null,
            $production && config('app.debug') ? 'APP_DEBUG is true — set it to false on the live server!' : null,
        ]));

        return self::SUCCESS;
    }
}
