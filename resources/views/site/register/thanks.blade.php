<x-layouts.app :title="$registrationSettings->thank_you_title">
@php $am = app()->getLocale() === 'am'; $rs = $registrationSettings; @endphp
<section class="page-hero" style="padding-bottom:90px">
  <div class="pattern pattern-layer"></div>
  <div class="container">
    <div class="thanks reveal">
      <div class="seal">{!! \App\Support\Icons::svg('check', '', 2.4) !!}</div>
      <span class="eyebrow">{{ $registration->batch?->program?->t('name') }} · {{ $registration->batch?->name }}</span>
      <h1>{{ $am ? ($rs->thank_you_title_am ?: $rs->thank_you_title) : $rs->thank_you_title }}</h1>
      <div class="divider"><i></i><b></b><i></i></div>
      <p>{!! nl2br(e(($header['thanks'] ?? null) ?: ($am ? ($rs->thank_you_text_am ?: $rs->thank_you_text) : $rs->thank_you_text))) !!}</p>
      <p style="font-size:.85rem;margin-top:10px">{{ __('site.form.reference') }}: <code>{{ strtoupper(substr($registration->ulid, -8)) }}</code></p>
      <div class="hero-cta" style="justify-content:center">
        @if ($botLink)
          <a href="{{ $botLink }}" class="btn tg-btn" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.9 4.3 18.7 19.4c-.2 1-.9 1.3-1.7.8l-4.8-3.5-2.3 2.2c-.3.3-.5.5-1 .5l.3-4.9 8.9-8c.4-.3-.1-.5-.6-.2l-11 6.9-4.7-1.5c-1-.3-1-1 .2-1.5L20.6 3c.9-.3 1.6.2 1.3 1.3z"/></svg>
            {{ __('site.form.get_updates') }}
          </a>
        @endif
        <a href="{{ route('home') }}" class="btn btn-ghost">{{ __('site.form.back_home') }}</a>
      </div>
    </div>
  </div>
</section>
</x-layouts.app>
