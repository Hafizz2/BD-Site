<?php

namespace App\Http\Requests;

use App\Models\Batch;
use App\Settings\RegistrationSettings;
use App\Support\PhoneNumber;
use App\Support\RegistrationForm;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates a registration against the form configured on its batch
 * (which built-in fields are shown / required, plus the batch's custom questions).
 */
class StoreRegistrationRequest extends FormRequest
{
    private ?Batch $batchModel = null;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $trim = fn ($v) => is_string($v) ? trim(preg_replace('/\s+/u', ' ', $v)) : $v;
        $this->merge(array_map($trim, $this->only(array_keys(RegistrationForm::BUILTIN))));
    }

    public function batch(): ?Batch
    {
        return $this->batchModel ??= Batch::find($this->input('batch_id'));
    }

    public function rules(): array
    {
        $phone = function (string $attribute, mixed $value, Closure $fail) {
            if (filled($value) && ! PhoneNumber::isValid($value)) {
                $fail(__('validation.phone_et'));
            }
        };

        $rules = [
            // Each batch has its own open/close switch ("Registration link open").
            'batch_id' => ['required', 'integer', function ($attr, $value, Closure $fail) {
                $batch = Batch::openForRegistration()->find($value);
                if (! $batch) {
                    $fail(__('validation.batch_closed'));
                } elseif ($batch->isFull()) {
                    $fail(__('validation.batch_full'));
                }
            }],
        ];

        $builtinRules = [
            'full_name' => ['string', 'min:3', 'max:150'],
            'gender' => ['in:male,female'],
            'date_of_birth' => ['date', 'before:-3 years', 'after:1920-01-01'],
            'phone' => ['string', 'max:20', $phone],
            'alt_phone' => ['string', 'max:20', $phone],
            'email' => ['email', 'max:150'],
            'school' => ['string', 'max:150'],
            'grade_or_year' => ['string', 'max:50'],
            'sub_city' => ['string', 'max:100'],
            'address' => ['string', 'max:200'],
            'guardian_name' => ['string', 'max:150'],
            'guardian_phone' => ['string', 'max:20', $phone],
            'guardian_relation' => ['string', 'max:50'],
            'notes' => ['string', 'max:1000'],
        ];

        foreach (RegistrationForm::enabled($this->batch()) as $f) {
            if ($f['type'] === 'section') {
                continue; // title & description block, nothing to answer
            }

            $required = $f['required'] ? 'required' : 'nullable';

            if ($f['type'] === 'checkbox') {
                // A required tick box (e.g. consent) must be ticked.
                $rules[$f['key']] = $f['required'] ? ['accepted'] : ['nullable', 'boolean'];

                continue;
            }

            if ($f['builtin']) {
                $rules[$f['key']] = [$required, ...$builtinRules[$f['key']]];

                continue;
            }

            $name = 'answers.'.$f['key'];
            $rules[$name] = match ($f['type']) {
                'textarea' => [$required, 'string', 'max:3000'],
                'number' => [$required, 'numeric'],
                'date' => [$required, 'date'],
                'yes_no' => [$required, 'in:yes,no'],
                'select' => [$required, Rule::in($f['options'])],
                'checkboxes' => [$required, 'array'],
                default => [$required, 'string', 'max:500'],
            };
            if ($f['type'] === 'checkboxes') {
                $rules[$name.'.*'] = [Rule::in($f['options'])];
            }
        }

        return $rules;
    }

    public function attributes(): array
    {
        return collect(RegistrationForm::enabled($this->batch()))
            ->reject(fn ($f) => $f['type'] === 'section')
            ->mapWithKeys(fn ($f) => [($f['builtin'] ? $f['key'] : 'answers.'.$f['key']) => RegistrationForm::label($f)])
            ->all();
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $settings = app(RegistrationSettings::class);
            if ($settings->turnstile_enabled && config('services.turnstile.secret')) {
                $ok = Http::asForm()->timeout(8)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('services.turnstile.secret'),
                    'response' => (string) $this->input('cf-turnstile-response'),
                    'remoteip' => $this->ip(),
                ])->json('success');
                if (! $ok) {
                    $v->errors()->add('turnstile', __('validation.turnstile'));
                }
            }
        }];
    }

    /** Worked out from the date of birth (for information only). */
    public function isMinor(): bool
    {
        $dob = $this->input('date_of_birth');
        if (! $dob) {
            return false;
        }
        try {
            return Carbon::parse($dob)->age < 18;
        } catch (\Throwable) {
            return false;
        }
    }

    public function registrationData(): array
    {
        $d = $this->validated();

        $data = [
            'batch_id' => (int) $d['batch_id'],
            'is_minor' => $this->isMinor(),
            'answers' => RegistrationForm::pack($this->batch(), $d['answers'] ?? []) ?: null,
        ];

        foreach (RegistrationForm::BUILTIN as $key => $type) {
            $data[$key] = $type === 'checkbox' ? filter_var($d[$key] ?? false, FILTER_VALIDATE_BOOLEAN) : ($d[$key] ?? null);
        }
        foreach (['phone', 'alt_phone', 'guardian_phone'] as $key) {
            $data[$key] = PhoneNumber::normalize($data[$key]);
        }

        return $data;
    }
}
