<?php

namespace App\Providers;

use App\Models\Member;
use App\Models\Registration;
use App\Models\Student;
use App\Models\User;
use App\Services\Telegram\FakeTelegramGateway;
use App\Services\Telegram\NutgramGateway;
use App\Services\Telegram\TelegramGateway;
use App\Settings\GeneralSettings;
use App\Settings\HomepageSettings;
use App\Settings\NavigationSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use SergiX44\Nutgram\Nutgram;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Outbound Telegram: real API when a token is configured, in-memory fake otherwise (tests / local without a bot).
        $this->app->singleton(TelegramGateway::class, function ($app) {
            if ($app->runningUnitTests() || blank(config('nutgram.token'))) {
                return new FakeTelegramGateway;
            }

            return new NutgramGateway($app->make(Nutgram::class));
        });
    }

    public function boot(): void
    {
        // Short, stable morph names for people (used by dues, payments, Telegram links).
        Relation::morphMap([
            'student' => Student::class,
            'member' => Member::class,
            'registration' => Registration::class,
        ]);

        // Every admin table turns into compact cards on phones (styled in resources/views/filament/theme.blade.php).
        // Desktop keeps the normal table. Lists with their own card design (registrations, students, members) are unaffected.
        \Filament\Tables\Table::configureUsing(fn (\Filament\Tables\Table $table) => $table->stackedOnMobile());

        // Super Admin can do everything.
        Gate::before(fn (User $user, string $ability) => $user->hasRole(User::ROLE_SUPER_ADMIN) ? true : null);

        // Policies for models outside app/Models (not auto-discovered).
        Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);
        Gate::policy(\Spatie\Activitylog\Models\Activity::class, \App\Policies\ActivityPolicy::class);

        // Rate limits
        RateLimiter::for('registration', fn (Request $r) => [
            Limit::perMinute(5)->by($r->ip()),
            Limit::perDay(30)->by($r->ip()),
        ]);
        RateLimiter::for('contact', fn (Request $r) => Limit::perMinute(3)->by($r->ip()));
        // Telegram allows ~30 msg/s globally; stay under it.
        RateLimiter::for('telegram', fn () => Limit::perSecond(25));

        // Settings available to every public view.
        View::composer(['components.layouts.*', 'components.site.*', 'site.*'], function ($view) {
            $view->with([
                'site' => app(GeneralSettings::class),
                'navigation' => app(NavigationSettings::class),
                'homepage' => app(HomepageSettings::class),
            ]);
        });
    }
}
