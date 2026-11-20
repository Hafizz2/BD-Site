@props(['logo'])
@php
    $am = app()->getLocale() === 'am';
    $l = fn ($row, $key) => ($am && filled($row[$key.'_am'] ?? null)) ? $row[$key.'_am'] : ($row[$key] ?? '');
    $tel = $site->phone ? preg_replace('/[^\d+]/', '', $site->phone) : null;
    $mapUrl = $site->t('address') ? 'https://maps.google.com/?q='.urlencode($site->t('address')) : null;
@endphp
<footer id="contact" class="site-foot">
  <div class="container">
    <div class="foot">
      {{-- Brand + quick contact --}}
      <div class="foot-brand">
        <a href="{{ route('home') }}" class="brand"><img src="{{ $logo }}" alt="" width="42" height="42"><div class="brand-text"><div class="am">{{ $site->site_name_am }}</div><div class="en">{{ $site->site_name }}</div></div></a>
        @if ($site->t('footer_about'))<p class="foot-about">{{ $site->t('footer_about') }}</p>@endif
        <div class="foot-actions">
          @if ($tel)<a href="tel:{{ $tel }}" aria-label="{{ __('site.contact_us') }}: {{ $site->phone }}" title="{{ $site->phone }}">{!! \App\Support\Icons::svg('phone') !!}</a>@endif
          @if ($site->email)<a href="mailto:{{ $site->email }}" aria-label="Email" title="{{ $site->email }}">{!! \App\Support\Icons::svg('mail') !!}</a>@endif
          @if ($mapUrl)<a href="{{ $mapUrl }}" target="_blank" rel="noopener" aria-label="Map" title="{{ $site->t('address') }}">{!! \App\Support\Icons::svg('map') !!}</a>@endif
          <x-site.socials />
        </div>
      </div>

      {{-- Link columns (chips on mobile, columns on desktop) --}}
      @foreach ($navigation->footer_columns as $col)
        @continue(empty($col['links']))
        <div class="foot-col">
          <h5>{{ $l($col, 'title') }}</h5>
          <div class="foot-links">
            @foreach ($col['links'] as $link)
              <a href="{{ url($link['url'] ?? '#') }}">{{ $l($link, 'label') }}</a>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>

    <div class="copy">
      <span>© {{ now()->year }} {{ $site->t('copyright') }}</span>
      @if ($site->t('address'))<span class="copy-addr">{{ $site->t('address') }}</span>@endif
    </div>
  </div>
</footer>
