<?php

use App\Models\Article;
use App\Models\Batch;
use App\Models\Donation;
use App\Models\Due;
use App\Models\Event;
use App\Models\Expense;
use App\Models\FeePlan;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Registration;
use App\Models\Student;
use App\Models\User;
use App\Support\RegistrationForm;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;

beforeEach(function () {
    config()->set('app.admin', ['name' => 'Boss', 'email' => 'boss@example.com', 'password' => 'secret-password']);
});

it('seeds sample website content on the live server, but no students and no finance', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful(); // exactly what jemea:setup runs

    // Sample content is there…
    expect(Program::count())->toBe(4)
        ->and(Batch::count())->toBe(5)
        ->and(Article::count())->toBe(5)
        ->and(Event::count())->toBe(3)
        ->and(Member::count())->toBe(0)                       // members are confidential: none seeded
        ->and(User::where('email', 'boss@example.com')->sole()->isSuperAdmin())->toBeTrue();

    // … with a sample Google-Forms style form, and every batch closed (nobody registers for a sample batch).
    $b6 = Batch::where('name', 'Batch 6')->sole();
    expect(RegistrationForm::header($b6)['title'])->toBe('Summer College Training — Batch 6')
        ->and(Batch::where('registration_open', true)->count())->toBe(0);

    // … but no people data or money.
    expect(Student::count())->toBe(0)
        ->and(Registration::count())->toBe(0)
        ->and(FeePlan::count())->toBe(0)
        ->and(Due::count())->toBe(0)
        ->and(Payment::count())->toBe(0)
        ->and(Expense::count())->toBe(0)
        ->and(Donation::count())->toBe(0)
        ->and(Member::whereNotNull('phone')->count())->toBe(0);
});

it('can be run again on every deploy without duplicating or overwriting', function () {
    app()->detectEnvironment(fn () => 'production');
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful(); // exactly what jemea:setup runs

    // Admin edits some sample content…
    Program::where('slug', 'madrasa')->update(['name' => 'Our Madrasa']);

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful(); // exactly what jemea:setup runs

    expect(Program::count())->toBe(4)
        ->and(Batch::count())->toBe(5)
        ->and(Article::count())->toBe(5)
        ->and(Member::count())->toBe(0)
        ->and(User::count())->toBe(1)
        ->and(Program::where('slug', 'madrasa')->value('name'))->toBe('Our Madrasa'); // edit kept
});

it('adds fake students and finance on a local machine only, and demo:cleanup removes them', function () {
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful(); // exactly what jemea:setup runs
    $this->seed(DemoSeeder::class);

    expect(Student::count())->toBeGreaterThanOrEqual(40)
        ->and(Payment::count())->toBeGreaterThan(0)
        ->and(Registration::count())->toBeGreaterThan(0);

    $this->artisan('demo:cleanup', ['--force' => true])->assertSuccessful();

    expect(Student::count())->toBe(0)
        ->and(Payment::count())->toBe(0)
        ->and(FeePlan::count())->toBe(0)
        ->and(Registration::count())->toBe(0)
        ->and(Program::count())->toBe(4)                            // sample content stays
        ->and(Member::count())->toBe(0);
});

it('has a one-step setup command', function () {
    $this->artisan('jemea:setup')->assertSuccessful();

    expect(Program::count())->toBe(4)->and(User::where('email', 'boss@example.com')->exists())->toBeTrue();
});
