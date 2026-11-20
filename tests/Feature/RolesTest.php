<?php

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    config()->set('app.admin', ['name' => 'Boss', 'email' => 'boss@example.com', 'password' => 'secret-password']);
    $this->seed(RolesSeeder::class);
});

function userWithRole(string $role): User
{
    return tap(User::factory()->create(['is_active' => true]))->assignRole($role);
}

it('creates the super admin from env and lets them do everything', function () {
    $admin = User::where('email', 'boss@example.com')->sole();

    expect($admin->isSuperAdmin())->toBeTrue()
        ->and($admin->can('viewAny', Payment::class))->toBeTrue()
        ->and($admin->can('approve_payment'))->toBeTrue();
});

it('gives each role only what the spec allows', function () {
    $finance = userWithRole(User::ROLE_FINANCE);
    $registrar = userWithRole(User::ROLE_REGISTRAR);
    $media = userWithRole(User::ROLE_MEDIA);

    expect($finance->can('viewAny', Payment::class))->toBeTrue()
        ->and($finance->can('approve_payment'))->toBeTrue()
        ->and($finance->can('viewAny', Student::class))->toBeTrue()
        ->and($finance->can('create', Student::class))->toBeFalse()   // read-only on students
        ->and($registrar->can('create', Student::class))->toBeTrue()
        ->and($registrar->can('viewAny', Payment::class))->toBeFalse() // no finance
        ->and($registrar->can('approve_payment'))->toBeFalse()
        ->and($media->can('viewAny', Student::class))->toBeFalse();
});

it('only lets active users with a role into the admin panel', function () {
    $noRole = User::factory()->create(['is_active' => true]);
    $inactive = tap(User::factory()->create(['is_active' => false]))->assignRole(User::ROLE_ADMIN);

    $this->actingAs($noRole)->get('/admin')->assertForbidden();
    $this->actingAs($inactive)->get('/admin')->assertForbidden();
    $this->actingAs(userWithRole(User::ROLE_ADMIN))->get('/admin')->assertOk();
});

it('is safe to run the roles seeder twice', function () {
    $this->seed(RolesSeeder::class);

    expect(User::where('email', 'boss@example.com')->count())->toBe(1);
});

it('has no teacher role any more', function () {
    expect(\Spatie\Permission\Models\Role::where('name', 'teacher')->exists())->toBeFalse();
});

it('lets only finance / coordinators manage contributors', function () {
    $member = \App\Models\Member::factory()->create();

    $this->actingAs(userWithRole(User::ROLE_MEDIA));
    \Livewire\Livewire::test(\App\Filament\Resources\MemberResource\Pages\ListMembers::class)
        ->assertTableBulkActionHidden('addContributors');

    $this->actingAs(userWithRole(User::ROLE_FINANCE));
    \Livewire\Livewire::test(\App\Filament\Resources\MemberResource\Pages\ListMembers::class)
        ->assertTableBulkActionVisible('addContributors');
});
