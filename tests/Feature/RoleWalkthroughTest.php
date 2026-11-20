<?php

/*
 * Walks through the admin as each role, the way a real person would:
 * log in with the login form, open pages, press the buttons they use every day.
 */

use App\Enums\EnrollmentStatus;
use App\Enums\RegistrationStatus;
use App\Filament\Resources\BatchResource;
use App\Filament\Resources\BatchResource\Pages\CreateBatch;
use App\Filament\Resources\MemberResource\Pages\EditMember;
use App\Filament\Resources\MemberResource\Pages\ListMembers;
use App\Filament\Resources\PaymentResource\Pages\CreatePayment;
use App\Filament\Resources\RegistrationResource;
use App\Filament\Resources\RegistrationResource\Pages\ListRegistrations;
use App\Filament\Resources\ServiceResource\Pages\CreateService;
use App\Filament\Resources\StudentResource\Pages\ListStudents;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\Batch;
use App\Models\Due;
use App\Models\FeePlan;
use App\Models\Member;
use App\Models\Program;
use App\Models\Registration;
use App\Models\Service;
use App\Models\Student;
use App\Models\User;
use App\Services\Finance\DuesGenerator;
use App\Support\RegistrationForm;
use Database\Seeders\RolesSeeder;
use Database\Seeders\WebsiteContentSeeder;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/** Every admin page: name => url. */
function adminPages(): array
{
    $panel = Filament::getPanel('admin');
    $urls = [];
    foreach ($panel->getResources() as $resource) {
        if ($resource::hasPage('index')) {
            $urls[str_replace('Resource', '', class_basename($resource))] = $resource::getUrl('index');
        }
    }
    foreach ($panel->getPages() as $page) {
        $urls[class_basename($page)] = $page::getUrl();
    }
    ksort($urls);

    return $urls;
}

/** Create a user with a role and log in through the real login form. */
function logInAs(string $role): User
{
    $user = User::factory()->create(['email' => "$role@example.com", 'password' => 'password123', 'is_active' => true]);
    $user->assignRole($role);
    auth()->logout();

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Livewire::test(Login::class)
        ->fillForm(['email' => "$role@example.com", 'password' => 'password123'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect(auth()->id())->toBe($user->id);

    return $user;
}

beforeEach(function () {
    config()->set('app.admin', ['name' => 'Boss', 'email' => 'boss@example.com', 'password' => 'secret-password']);
    $this->seed(RolesSeeder::class);
    $this->seed(WebsiteContentSeeder::class);
});

it('gives each role exactly its own pages', function () {
    // Pages each role should open (everything else must be refused).
    $common = ['Dashboard'];
    $expected = [
        'admin' => ['Announcement', 'Batch', 'ContactMessage', 'Import', 'ImportStudents', 'ManageHomepage', 'ManageNavigation',
            'ManageRegistration', 'ManageSite', 'Member', 'NotificationLog', 'Program', 'Registration', 'RegistrationForms', 'Student'],
        'finance' => ['Batch', 'Donation', 'Due', 'Expense', 'FeePlan', 'FinanceReports', 'ManagePayments', 'ManageReminders',
            'Member', 'Payment', 'Program', 'Student'],
        'registrar' => ['Batch', 'Import', 'ImportStudents', 'Program', 'Registration', 'Student'],
        'media' => ['Article', 'ArticleCategory', 'ContactMessage', 'Event', 'Faq', 'GalleryAlbum', 'Goal', 'JourneyStep',
            'ManageHomepage', 'ManageNavigation', 'ManageRegistration', 'ManageSite', 'Member', 'Page', 'Service', 'Stat'],
    ];

    foreach ($expected as $role => $allowed) {
        logInAs($role);
        $opened = [];
        foreach (adminPages() as $name => $url) {
            $status = $this->get($url)->status();
            expect($status)->toBeIn([200, 403], "$role → $name gave HTTP $status");
            if ($status === 200) {
                $opened[] = $name;
            }
        }
        expect($opened)->toEqualCanonicalizing([...$common, ...$allowed], "Pages for $role");
    }

    // Super admin opens everything.
    logInAs('super_admin');
    foreach (adminPages() as $name => $url) {
        expect($this->get($url)->status())->toBe(200, "super_admin → $name");
    }
});

it('admin: sets up a batch form, shares the link, calls, interviews, accepts and makes a member', function () {
    $program = Program::factory()->create(['name' => 'College Training']);
    logInAs('admin');

    // 1. Create a batch (it gets the standard form) …
    Livewire::test(CreateBatch::class)
        ->fillForm(['program_id' => $program->id, 'name' => 'Batch 7', 'season' => 'summer', 'status' => 'active', 'registration_open' => true])
        ->call('create')
        ->assertHasNoFormErrors();
    $batch = Batch::where('name', 'Batch 7')->sole();
    expect(array_column(RegistrationForm::enabled($batch), 'key'))->not->toContain('guardian_consent', 'photo_consent');

    // … and add a question to its form.
    $fields = RegistrationForm::all($batch);
    $fields[] = ['key' => 'q_days', 'type' => 'select', 'label' => 'Which day suits you?', 'label_am' => null, 'help' => null,
        'required' => true, 'enabled' => true, 'options' => ['Saturday', 'Sunday']];
    Livewire::test(BatchResource\Pages\EditBatch::class, ['record' => $batch->getRouteKey()])
        ->fillForm(['form_fields' => $fields])
        ->call('save')
        ->assertHasNoFormErrors();
    expect(collect($batch->fresh()->form_fields)->pluck('key'))->toContain('q_days');

    // 2. The batch page shows its registration link.
    $this->get(BatchResource::getUrl('view', ['record' => $batch]))->assertOk()->assertSee('Registration link');

    // 3. Someone registers through the link (as a visitor).
    auth()->logout();
    $this->get(route('register.batch', $batch))->assertOk()->assertSee('Which day suits you?');
    $this->post('/register', ['batch_id' => $batch->id, 'full_name' => 'Hanan Kemal', 'gender' => 'female', 'phone' => '0911 22 33 44',
        'answers' => ['q_days' => 'Sunday']])->assertSessionHasNoErrors();
    $reg = Registration::sole();

    // Admin logs back in.
    $this->flushSession();
    Livewire::test(Login::class)->fillForm(['email' => 'admin@example.com', 'password' => 'password123'])->call('authenticate')->assertHasNoFormErrors();

    // 4. Registrations page: All tab shows it; the batch page lists it too.
    Livewire::test(ListRegistrations::class)->assertCanSeeTableRecords([$reg]);
    $this->get(RegistrationResource::getUrl('view', ['record' => $reg]))->assertOk()->assertSee('Which day suits you?')->assertSee('Sunday');

    // 5. Call → Interview → Accepted.
    Livewire::test(ListRegistrations::class)
        ->callTableAction('logCall', $reg, ['outcome' => 'Talked', 'note' => 'Coming Saturday'])
        ->assertHasNoTableActionErrors()
        ->callTableAction('updateStatus', $reg, ['status' => 'interview', 'note' => 'Saturday 10:00'])
        ->assertHasNoTableActionErrors();
    expect($reg->fresh()->status)->toBe(RegistrationStatus::Interview)->and($reg->fresh()->call_note)->toBe('Talked — Coming Saturday');

    Livewire::test(ListRegistrations::class)->set('activeTab', 'interview')
        ->assertCanSeeTableRecords([$reg])
        ->callTableAction('updateStatus', $reg, ['status' => 'approved'])
        ->assertHasNoTableActionErrors();
    $student = Student::where('phone', '+251911223344')->sole();
    expect($reg->fresh()->status)->toBe(RegistrationStatus::Approved)
        ->and($student->enrollments()->sole()->status)->toBe(EnrollmentStatus::Approved);

    // 6. Excel download of the list.
    Livewire::test(ListRegistrations::class)->callTableAction('export')->assertFileDownloaded();

    // 7. After the course: make them a member and pick a team (no consent tick needed).
    Livewire::test(ListStudents::class)
        ->callTableAction('makeMember', $student, ['team' => 'media'])
        ->assertHasNoTableActionErrors();
    expect(Member::where('student_id', $student->id)->value('team')->value)->toBe('media');

    // 8. Not allowed: money pages.
    $this->get(\App\Filament\Resources\PaymentResource::getUrl('index'))->assertForbidden();
});

it('registrar: works through registrations but cannot touch money or members', function () {
    $reg = Registration::factory()->create();
    logInAs('registrar');

    Livewire::test(ListRegistrations::class)
        ->assertCanSeeTableRecords([$reg])
        ->callTableAction('updateStatus', $reg, ['status' => 'waitlisted'])
        ->assertHasNoTableActionErrors();
    expect($reg->fresh()->status)->toBe(RegistrationStatus::Waitlisted);

    $this->get(\App\Filament\Pages\ImportStudents::getUrl())->assertOk();
    $this->get(\App\Filament\Resources\PaymentResource::getUrl('index'))->assertForbidden();
    $this->get(\App\Filament\Resources\MemberResource::getUrl('index'))->assertForbidden();
});

it('finance: picks contributors, generates dues and records a payment', function () {
    $plan = FeePlan::factory()->monthlyContribution()->create(['amount' => '200.00']);
    $member = Member::factory()->create(['is_active' => true]);
    logInAs('finance');

    // Member agrees to give → add to contributors.
    Livewire::test(ListMembers::class)
        ->callTableBulkAction('addContributors', [$member], ['plan' => $plan->id])
        ->assertHasNoTableBulkActionErrors();
    expect($member->fresh()->monthly_contribution_plan_id)->toBe($plan->id);

    app(DuesGenerator::class)->generateMonthly();
    $due = Due::sole();

    // Record the payment (approved straight away by default).
    Livewire::test(CreatePayment::class)
        ->fillForm(['payable_type' => 'member', 'payable_id' => $member->id, 'due_id' => $due->id, 'amount' => '200', 'method' => 'telebirr'])
        ->call('create')
        ->assertHasNoFormErrors();
    expect($due->fresh()->status->value)->toBe('paid');

    $this->get(\App\Filament\Pages\FinanceReports::getUrl())->assertOk();
    $this->get(RegistrationResource::getUrl('index'))->assertForbidden();
});

it('media: edits the website and cannot see who pays', function () {
    $member = Member::factory()->create();
    logInAs('media');

    Livewire::test(CreateService::class)
        ->fillForm(['title' => 'Stage Da\'wah', 'summary' => 'Public programs', 'is_active' => true])
        ->call('create')
        ->assertHasNoFormErrors();
    expect(Service::where('title', 'Stage Da\'wah')->exists())->toBeTrue();
    $this->get('/services')->assertSee('Stage Da&#039;wah', false);

    // Can edit the team page details, but not contributions.
    Livewire::test(EditMember::class, ['record' => $member->getRouteKey()])
        ->assertFormFieldIsHidden('monthly_contribution_plan_id');
    Livewire::test(ListMembers::class)->assertTableBulkActionHidden('addContributors');

    $this->get(\App\Filament\Resources\StudentResource::getUrl('index'))->assertForbidden();
});

it('super admin: adds a new admin user who can then log in', function () {
    logInAs('super_admin');

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'New Coordinator', 'email' => 'coord@example.com', 'password' => 'strongpass1',
            'roles' => [Role::findByName('admin')->id], 'is_active' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    auth()->logout();
    Livewire::test(Login::class)->fillForm(['email' => 'coord@example.com', 'password' => 'strongpass1'])->call('authenticate')->assertHasNoFormErrors();
    $this->get('/admin')->assertOk();
});

it('finds people by name or phone in the card lists', function () {
    $hanan = Registration::factory()->create(['full_name' => 'Hanan Kemal', 'phone' => '+251911223344']);
    $other = Registration::factory()->create(['full_name' => 'Yusuf Ali', 'phone' => '+251922000000']);
    logInAs('admin');

    Livewire::test(ListRegistrations::class)->searchTable('Hanan')
        ->assertCanSeeTableRecords([$hanan])->assertCanNotSeeTableRecords([$other]);
    Livewire::test(ListRegistrations::class)->searchTable('922000')
        ->assertCanSeeTableRecords([$other])->assertCanNotSeeTableRecords([$hanan]);

    $s = Student::factory()->create(['full_name' => 'Meryem Nuru']);
    Livewire::test(ListStudents::class)->searchTable('Meryem')->assertCanSeeTableRecords([$s])->assertSee('Meryem Nuru');
});
