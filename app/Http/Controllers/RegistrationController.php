<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Batch;
use App\Models\Page;
use App\Models\Program;
use App\Models\Registration;
use App\Services\Telegram\TelegramLinker;
use App\Settings\RegistrationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    /**
     * Registration is by link only: each batch has its own link (Admin → Batches → "Copy registration link"),
     * shared on Telegram / the website when that batch opens. /register without a batch just shows the closed message.
     */
    public function create(Request $request, RegistrationSettings $settings, ?Batch $batch = null): View
    {
        $open = $batch
            && Batch::openForRegistration()->whereKey($batch->id)
                ->whereHas('program', fn ($p) => $p->where('is_active', true))->exists();

        // Admins can preview any batch's form (Admin → Batches → "Preview form"), even while it is closed.
        $preview = $batch && $request->boolean('preview') && $request->user()?->can('update', $batch);

        $batches = ($open || $preview) ? collect([$batch->load('program')]) : collect();

        return view('site.register.form', [
            'page' => Page::system('register'),
            'registration' => $settings,
            'batches' => $batches,
            'fixedBatch' => $batches->first(),
            'preview' => $preview && ! $open,
        ]);
    }

    public function store(StoreRegistrationRequest $request): RedirectResponse
    {
        // Honeypot: silently pretend success for bots.
        if (filled($request->input('company'))) {
            return redirect()->route('home');
        }

        $data = $request->registrationData();
        $registration = Registration::create($data + [
            'status' => RegistrationStatus::Pending,
            'locale' => app()->getLocale(),
            'ip_address' => $request->ip(),
            'consent_at' => ! empty($data['guardian_consent']) ? now() : null,
        ]);

        // Only this browser session may view the thank-you page.
        $request->session()->put('registration_thanks', $registration->ulid);

        return redirect()->route('register.thanks', $registration);
    }

    public function thanks(Request $request, Registration $registration, RegistrationSettings $settings, TelegramLinker $linker): View
    {
        abort_unless($request->session()->get('registration_thanks') === $registration->ulid, 404);

        return view('site.register.thanks', [
            'registration' => $registration->load('batch.program'),
            'header' => $registration->batch ? \App\Support\RegistrationForm::header($registration->batch) : null,
            'registrationSettings' => $settings,
            'botLink' => $linker->deepLinkFor($registration),
        ]);
    }
}
