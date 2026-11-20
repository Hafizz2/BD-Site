@php
    $am = app()->getLocale() === 'am';
    $header = $fixedBatch ? \App\Support\RegistrationForm::header($fixedBatch) : null;
    $showForm = (bool) $fixedBatch; // each batch has its own open/close switch
@endphp
<x-layouts.app :title="$header['title'] ?? $page?->t('title')" :description="$header['description'] ?? ($page?->seo_description ?: $page?->t('intro'))">
@if ($registration->turnstile_enabled && $registration->turnstile_site_key)
  @push('head')<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endpush
@endif

@if (! $showForm)
  {{-- No (open) batch link → friendly closed message --}}
  <x-site.page-hero :page="$page" :crumbs="[$page?->t('title') => null]" />
  <section class="section" style="padding-top:40px">
    <div class="container" style="max-width:720px">
      <div class="empty reveal">
        {!! \App\Support\Icons::svg('calendar') !!}
        <p>{{ ($am ? ($registration->closed_message_am ?: $registration->closed_message) : $registration->closed_message) ?: __('site.form.no_batches') }}</p>
        @if ($site->telegram_url)<a href="{{ $site->telegram_url }}" class="btn btn-primary btn-sm mt-24" target="_blank" rel="noopener">Telegram</a>@endif
      </div>
    </div>
  </section>
@else
  {{-- Google-Forms-style registration form --}}
  <section class="gform-wrap">
    <div class="gform">
      @if ($preview)
        <div class="gform-preview">👁 {{ $am ? 'ቅድመ እይታ — ምዝገባው ዝግ ነው' : 'Preview — this batch\'s registration link is closed, so submitting won\'t work.' }}</div>
      @endif

      @if ($header['cover'])
        <div class="gform-cover"><img src="{{ $header['cover'] }}" alt=""></div>
      @endif

      <div class="gform-card gform-head">
        <h1>{{ $header['title'] }}</h1>
        @if ($header['description'])
          <div class="gform-desc">{!! nl2br(e($header['description'])) !!}</div>
        @endif
        <div class="gform-meta">
          @if ($fixedBatch->start_date)<span>{!! \App\Support\Icons::svg('calendar') !!}{{ $fixedBatch->start_date->translatedFormat('d M Y') }}</span>@endif
          @if ($fixedBatch->location)<span>{!! \App\Support\Icons::svg('map') !!}{{ $fixedBatch->location }}</span>@endif
          @php $sched = $am ? ($fixedBatch->schedule_note_am ?: $fixedBatch->schedule_note) : $fixedBatch->schedule_note; @endphp
          @if ($sched)<span>{!! \App\Support\Icons::svg('clock') !!}{{ $sched }}</span>@endif
        </div>
        <div class="gform-req">* {{ $am ? 'የግድ መሞላት ያለበት' : 'Required' }}</div>
      </div>

      @if ($errors->any())
        <div class="gform-card gform-errors">
          {{ $am ? 'እባክዎ ምልክት የተደረገባቸውን ያስተካክሉ።' : 'Please check the highlighted questions.' }}
        </div>
      @endif

      <form method="post" action="{{ route('register.store') }}" id="registerForm" novalidate>
        @csrf
        <div class="hp" aria-hidden="true"><label>Company <input type="text" name="company" tabindex="-1" autocomplete="off"></label></div>
        <input type="hidden" name="batch_id" value="{{ $fixedBatch->id }}">
        @error('batch_id')<div class="gform-card gform-errors">{{ $message }}</div>@enderror

        @foreach (\App\Support\RegistrationForm::enabled($fixedBatch) as $f)
          @if ($f['type'] === 'section')
            <div class="gform-card gform-section">
              <h2>{{ \App\Support\RegistrationForm::label($f) }}</h2>
              @if ($f['help'])<p>{!! nl2br(e($f['help'])) !!}</p>@endif
            </div>
          @else
            @php $dot = $f['builtin'] ? $f['key'] : 'answers.'.$f['key']; @endphp
            <div @class(['gform-card', 'gform-q', 'has-error' => $errors->has($dot) || $errors->has($dot.'.*')])>
              @include('site.register.field', ['f' => $f])
            </div>
          @endif
        @endforeach

        @if ($registration->turnstile_enabled && $registration->turnstile_site_key)
          <div class="gform-card"><div class="cf-turnstile" data-sitekey="{{ $registration->turnstile_site_key }}"></div>
          @error('turnstile')<div class="err">{{ $message }}</div>@enderror</div>
        @endif

        <div class="gform-submit">
          <button class="btn btn-primary" type="submit" @disabled($preview)>{{ __('site.form.submit') }} {!! \App\Support\Icons::svg('arrow', '', 2) !!}</button>
        </div>
      </form>
    </div>
  </section>
@endif
</x-layouts.app>
