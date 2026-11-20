<?php

use App\Exports\RegistrationsExport;
use App\Filament\Resources\BatchResource;
use App\Filament\Resources\RegistrationResource;
use App\Filament\Resources\RegistrationResource\Pages\EditRegistration;
use App\Filament\Resources\RegistrationResource\Pages\ListRegistrations;
use App\Models\Batch;
use App\Models\Registration;
use App\Models\User;
use App\Support\RegistrationForm;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

/** A batch whose form hides gender, renames "school", and adds two custom questions. */
function batchWithCustomForm(): Batch
{
    $fields = collect(RegistrationForm::defaults())->map(function ($f) {
        if ($f['key'] === 'gender') {
            $f['enabled'] = false;
        }
        if ($f['key'] === 'school') {
            $f['label'] = 'Which university?';
            $f['required'] = true;
        }

        return $f;
    })->all();

    $fields[] = ['key' => 'q_days', 'type' => 'select', 'label' => 'Which day suits you?', 'label_am' => null,
        'required' => true, 'enabled' => true, 'options' => ['Saturday', 'Sunday'], 'help' => null];
    $fields[] = ['key' => 'q_topics', 'type' => 'checkboxes', 'label' => 'Topics you studied', 'label_am' => null,
        'required' => false, 'enabled' => true, 'options' => ['Tawhid', 'Fiqh', 'Seerah'], 'help' => null];

    return Batch::factory()->create(['form_fields' => $fields]);
}

it('shows the batch\'s own form', function () {
    $batch = batchWithCustomForm();

    $this->get(route('register.batch', $batch))->assertOk()
        ->assertSee('Which university?')
        ->assertSee('Which day suits you?')
        ->assertSee('Seerah')
        ->assertDontSee('name="gender"', false);
});

it('validates and stores custom answers', function () {
    $batch = batchWithCustomForm();
    $base = ['batch_id' => $batch->id, 'full_name' => 'Hanan Kemal', 'phone' => '0911223344'];

    // Hidden gender is no longer required; required custom question + relabelled school are.
    $this->post('/register', $base)->assertSessionHasErrors(['answers.q_days', 'school'])->assertSessionDoesntHaveErrors('gender');
    $this->post('/register', $base + ['school' => 'AAU', 'answers' => ['q_days' => 'Monday']])->assertSessionHasErrors('answers.q_days');

    $this->post('/register', $base + ['school' => 'AAU', 'answers' => ['q_days' => 'Sunday', 'q_topics' => ['Fiqh', 'Seerah']]])
        ->assertSessionHasNoErrors();

    $r = Registration::sole();
    expect($r->school)->toBe('AAU')
        ->and($r->gender)->toBeNull()
        ->and($r->answers)->toBe([
            'q_days' => ['q' => 'Which day suits you?', 'a' => 'Sunday'],
            'q_topics' => ['q' => 'Topics you studied', 'a' => ['Fiqh', 'Seerah']],
        ]);
});

it('always keeps name and phone even if removed from the form', function () {
    $batch = Batch::factory()->create(['form_fields' => [
        ['key' => 'email', 'label' => 'Email', 'enabled' => true, 'required' => false],
    ]]);

    expect(array_column(RegistrationForm::enabled($batch), 'key'))->toBe(['full_name', 'phone', 'email']);
});

it('exports registrations with one column per question', function () {
    $batch = batchWithCustomForm();
    Registration::factory()->create(['batch_id' => $batch->id, 'full_name' => 'Hanan Kemal',
        'answers' => ['q_days' => ['q' => 'Which day suits you?', 'a' => 'Sunday'], 'q_topics' => ['q' => 'Topics you studied', 'a' => ['Fiqh', 'Seerah']]]]);

    $export = new RegistrationsExport(Registration::query());
    $row = $export->map($export->collection()->first());
    $headings = $export->headings();

    expect($headings)->toContain('Which day suits you?', 'Topics you studied')
        ->and($row[array_search('Which day suits you?', $headings)])->toBe('Sunday')
        ->and($row[array_search('Topics you studied', $headings)])->toBe('Fiqh, Seerah');
});

describe('admin', function () {
    beforeEach(function () {
        config()->set('app.admin', ['name' => 'Boss', 'email' => 'boss@example.com', 'password' => 'secret-password']);
        $this->seed(RolesSeeder::class);
        $this->actingAs(User::where('email', 'boss@example.com')->sole());
    });

    it('opens the batch form editor and registration pages', function () {
        $batch = batchWithCustomForm();
        $r = Registration::factory()->create(['batch_id' => $batch->id, 'answers' => ['q_days' => ['q' => 'Which day suits you?', 'a' => 'Sunday']]]);

        $this->get(BatchResource::getUrl('edit', ['record' => $batch]))->assertOk()->assertSee('Registration form');
        $this->get(BatchResource::getUrl('create'))->assertOk();
        $this->get(RegistrationResource::getUrl('view', ['record' => $r]))->assertOk()->assertSee('Which day suits you?')->assertSee('Sunday');
        $this->get(RegistrationResource::getUrl('edit', ['record' => $r]))->assertOk();
    });

    it('edits answers from admin', function () {
        $batch = batchWithCustomForm();
        $r = Registration::factory()->create(['batch_id' => $batch->id, 'answers' => ['q_days' => ['q' => 'Which day suits you?', 'a' => 'Sunday']]]);

        Livewire::test(EditRegistration::class, ['record' => $r->getRouteKey()])
            ->assertSet('data.answers.q_days', 'Sunday')
            ->set('data.answers.q_days', 'Saturday')
            ->call('save')
            ->assertHasNoFormErrors();

        expect($r->fresh()->answers['q_days'])->toBe(['q' => 'Which day suits you?', 'a' => 'Saturday']);
    });

    it('lists every batch form on the Registration forms page', function () {
        $custom = batchWithCustomForm();
        $custom->update(['form_settings' => ['title' => 'Summer Form 2026'], 'registration_open' => false]);
        $plain = Batch::factory()->create();

        Livewire::test(\App\Filament\Pages\RegistrationForms::class)
            ->assertCanSeeTableRecords([$custom, $plain])
            ->assertSee('Summer Form 2026')
            ->call('updateTableColumnState', 'registration_open', (string) $custom->getKey(), true);
        expect($custom->fresh()->registration_open)->toBeTrue();

        // "Edit form" lands on the Registration form tab.
        $this->get(BatchResource::getUrl('edit', ['record' => $custom, 'tab' => 'form']))
            ->assertOk()->assertSee('Form header');
        $this->get(\App\Filament\Pages\Settings\ManageRegistration::getUrl())->assertOk()->assertSee('Default thank-you page');
    });

    it('logs a call', function () {
        $r = Registration::factory()->create();

        Livewire::test(ListRegistrations::class)
            ->callTableAction('logCall', $r, ['outcome' => 'No answer', 'note' => 'try evening'])
            ->assertHasNoTableActionErrors();

        expect($r->fresh()->called_at)->not->toBeNull()
            ->and($r->fresh()->call_note)->toBe('No answer — try evening');
    });
});

it('shows the Google-Forms style header, section blocks and custom thank-you', function () {
    $batch = batchWithCustomForm();
    $fields = $batch->form_fields;
    array_splice($fields, 2, 0, [['key' => 'q_sec', 'type' => 'section', 'label' => 'About your studies', 'help' => 'Where do you study?', 'enabled' => true]]);
    $batch->update(['form_fields' => $fields, 'form_settings' => [
        'title' => 'Summer Training 2026', 'description' => "Welcome!\nPlease fill this in.", 'thanks' => 'We will call you soon.',
    ]]);

    $this->get(route('register.batch', $batch))->assertOk()
        ->assertSee('Summer Training 2026')->assertSee('Please fill this in.')
        ->assertSee('About your studies')->assertSee('Where do you study?');

    // The section block is not a question: no answer is required or stored for it.
    $this->post('/register', ['batch_id' => $batch->id, 'full_name' => 'Hanan Kemal', 'phone' => '0911223344', 'school' => 'AAU',
        'answers' => ['q_days' => 'Sunday']])->assertSessionHasNoErrors();
    $r = Registration::sole();
    expect($r->answers)->not->toHaveKey('q_sec');

    $this->get(route('register.thanks', $r))->assertSee('We will call you soon.');
});

it('lets admins preview a closed form, but not visitors', function () {
    $batch = Batch::factory()->closed()->create();

    $this->get(route('register.batch', ['batch' => $batch, 'preview' => 1]))->assertDontSee('registerForm');

    config()->set('app.admin', ['name' => 'Boss', 'email' => 'boss@example.com', 'password' => 'secret-password']);
    $this->seed(RolesSeeder::class);
    $this->actingAs(User::where('email', 'boss@example.com')->sole())
        ->get(route('register.batch', ['batch' => $batch, 'preview' => 1]))
        ->assertSee('registerForm')->assertSee('Preview');
});
