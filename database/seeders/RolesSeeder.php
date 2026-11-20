<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles from the spec (section 2) + the first Super Admin from .env.
 * Safe to run again: it only syncs permissions and never duplicates.
 */
class RolesSeeder extends Seeder
{
    /** role => [full access models, read-only models, extra permissions] */
    private const ROLES = [
        User::ROLE_ADMIN => [
            ['Program', 'Batch', 'Student', 'Registration', 'Import', 'Announcement', 'Member', 'ContactMessage'],
            ['NotificationLog'],
            [],
        ],
        User::ROLE_FINANCE => [
            ['FeePlan', 'Due', 'Payment', 'Expense', 'Donation'],
            ['Student', 'Member', 'Program', 'Batch'],
            ['approve_payment'],
        ],
        User::ROLE_REGISTRAR => [
            ['Registration', 'Student', 'Import'],
            ['Program', 'Batch'],
            [],
        ],
        User::ROLE_MEDIA => [
            ['Page', 'Service', 'Event', 'GalleryAlbum', 'Member', 'Testimonial', 'Faq', 'Stat', 'Goal',
                'JourneyStep', 'Article', 'ArticleCategory', 'ContactMessage'],
            [],
            [],
        ],
    ];

    public function run(): void
    {
        // Make sure Shield's per-resource permissions exist (idempotent).
        // One permission per admin page × action, named like Shield does ("ViewAny:Student").
        // Built here instead of calling `shield:generate`, which refuses to run in production.
        $methods = config('filament-shield.policies.methods', ['viewAny', 'view', 'create', 'update', 'delete', 'deleteAny']);
        foreach (\Filament\Facades\Filament::getPanel('admin')->getResources() as $resource) {
            $model = class_basename($resource::getModel());
            foreach ($methods as $method) {
                Permission::findOrCreate(\Illuminate\Support\Str::studly($method).':'.$model, 'web');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate(User::ROLE_SUPER_ADMIN, 'web');
        Role::where('name', 'teacher')->delete(); // attendance / teacher role was removed

        // Shield also creates a "panel_user" role with no permissions; we don't use it.
        Role::where('name', 'panel_user')->whereDoesntHave('users')->delete();
        Permission::findOrCreate('approve_payment', 'web');

        $all = Permission::pluck('name');

        foreach (self::ROLES as $role => [$full, $readOnly, $extra]) {
            $names = $all->filter(function (string $p) use ($full, $readOnly) {
                [$action, $model] = array_pad(explode(':', $p, 2), 2, null);

                return in_array($model, $full, true)
                    || (in_array($model, $readOnly, true) && in_array($action, ['ViewAny', 'View'], true));
            })->merge($extra)->values();

            Role::findOrCreate($role, 'web')->syncPermissions($names);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // First Super Admin from .env.
        $email = config('app.admin.email');
        $password = config('app.admin.password');
        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set in .env — no Super Admin created.');

            return;
        }

        $admin = User::firstOrCreate(['email' => $email], [
            'name' => config('app.admin.name', 'Super Admin'),
            'password' => $password,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole(User::ROLE_SUPER_ADMIN);
    }
}
