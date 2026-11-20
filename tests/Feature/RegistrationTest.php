<?php

use App\Enums\EnrollmentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Batch;
use App\Models\Registration;
use App\Models\Student;
use App\Services\RegistrationService;

function validRegistration(Batch $batch, array $overrides = []): array
{
    return array_merge([
        'batch_id' => $batch->id,
        'full_name' => 'Hanan Kemal',
        'gender' => 'female',
        'date_of_birth' => '2000-05-14',
        'phone' => '0911 22 33 44',
    ], $overrides);
}

it('accepts a registration and normalizes the phone', function () {
    $batch = Batch::factory()->create();

    $response = $this->post('/register', validRegistration($batch));

    $registration = Registration::sole();
    $response->assertRedirect(route('register.thanks', $registration));
    expect($registration->phone)->toBe('+251911223344')
        ->and($registration->status)->toBe(RegistrationStatus::Pending);

    $this->get(route('register.thanks', $registration))->assertOk();
});

it('does not ask for consent unless the admin turns it on', function () {
    $batch = Batch::factory()->create();

    // Default form: no guardian / consent fields, even for an under-18.
    $this->get(route('register.batch', $batch))->assertDontSee('name="guardian_consent"', false)->assertDontSee('name="photo_consent"', false);
    $this->post('/register', validRegistration($batch, ['date_of_birth' => now()->subYears(15)->toDateString()]))->assertSessionHasNoErrors();

    // Admin switches on a required consent box for another batch.
    $fields = collect(\App\Support\RegistrationForm::defaults())->map(fn ($f) => $f['key'] === 'guardian_consent' ? [...$f, 'enabled' => true, 'required' => true] : $f)->all();
    $kids = Batch::factory()->create(['form_fields' => $fields]);

    $this->get(route('register.batch', $kids))->assertSee('name="guardian_consent"', false);
    $this->post('/register', validRegistration($kids))->assertSessionHasErrors('guardian_consent');
    $this->post('/register', validRegistration($kids, ['phone' => '0922334455', 'guardian_consent' => '1']))->assertSessionHasNoErrors();

    expect(Registration::latest('id')->first()->guardian_consent)->toBeTrue();
});

it('rejects invalid phones and closed batches', function () {
    $closed = Batch::factory()->closed()->create();

    $this->post('/register', validRegistration($closed, ['phone' => '12345']))
        ->assertSessionHasErrors(['phone', 'batch_id']);
});

it('silently drops honeypot submissions', function () {
    $batch = Batch::factory()->create();

    $this->post('/register', validRegistration($batch, ['company' => 'spam inc']))->assertRedirect(route('home'));

    expect(Registration::count())->toBe(0);
});

it('hides the thank-you page from other visitors', function () {
    $registration = Registration::factory()->create();

    $this->get(route('register.thanks', $registration))->assertNotFound();
});

it('approving creates a student and enrollment, and reuses the student by phone', function () {
    $batch = Batch::factory()->create();
    $this->post('/register', validRegistration($batch));
    $first = Registration::sole();

    $student = app(RegistrationService::class)->approve($first);

    expect($student->phone)->toBe('+251911223344')
        ->and($student->enrollments()->sole()->status)->toBe(EnrollmentStatus::Approved)
        ->and($first->fresh()->status)->toBe(RegistrationStatus::Approved);

    // Same person registers for another batch with a differently formatted phone.
    $other = Batch::factory()->create();
    $this->post('/register', validRegistration($other, ['phone' => '+251-911-223344']));
    $second = Registration::latest('id')->first();

    $again = app(RegistrationService::class)->approve($second);

    expect($again->id)->toBe($student->id)
        ->and(Student::count())->toBe(1)
        ->and($student->enrollments()->count())->toBe(2);
});

it('only shows the form through an open batch link', function () {
    $open = Batch::factory()->create();
    $closed = Batch::factory()->closed()->create();

    $this->get('/register')->assertOk()->assertDontSee('registerForm');
    $this->get(route('register.batch', $closed))->assertOk()->assertDontSee('registerForm');
    $this->get(route('register.batch', $open))->assertOk()->assertSee('registerForm')
        ->assertSee('name="batch_id" value="'.$open->id.'"', false);
});

it('moves a registration to interview', function () {
    $registration = Registration::factory()->create();

    app(RegistrationService::class)->inviteToInterview($registration, null, 'Saturday 10:00 at the mosque');

    expect($registration->fresh()->status)->toBe(RegistrationStatus::Interview);
});
