@props(['logo', 'solid' => true])
@php
    $am = app()->getLocale() === 'am';
    $label = fn ($item) => ($am && filled($item['label_am'] ?? null)) ? $item['label_am'] : ($item['label'] ?? '');
    $path = fn ($url) => trim(parse_url((string) $url, PHP_URL_PATH) ?? '', '/');
    $isActive = function ($url) use ($path) {
        $p = $path($url);
        return $p === '' ? request()->is('/') : request()->is($p, $p.'/*');
    };

    // Mobile menu: one flat list of tiles (sub-pages become their own tiles — no dropdowns).
    $tiles = collect();
    foreach ($navigation->header_links as $item) {
        foreach (! empty($item['children']) ? $item['children'] : [$item] as $link) {
            $tiles->push($link);
        }
    }
    $tiles = $tiles->unique(fn ($l) => $path($l['url'] ?? ''))->values();
    $icon = function ($url) use ($path) {
        $p = $path($url);
        foreach ([
            'about' => 'users', 'goal' => 'target', 'team' => 'hands', 'faq' => 'chat', 'service' => 'star',
            'program' => 'graduation', 'article' => 'pen', 'event' => 'calendar', 'announcement' => 'mic',
            'gallery' => 'video', 'contact' => 'mail', 'donate' => 'heart', 'register' => 'check',
        ] as $needle => $svg) {
            if (str_contains($p, $needle)) {
                return $svg;
            }
        }
        return $p === '' ? 'mosque' : 'compass';
    };
    $cta = $navigation->header_cta_url ? ['url' => $navigation->header_cta_url, 'label' => $am ? ($navigation->header_cta_label_am ?: $navigation->header_cta_label) : $navigation->header_cta_label] : null;
@endphp
<header class="nav {{ $solid ? 'solid' : '' }}" id="nav">
  <div class="container nav-inner">
    <a href="{{ route('home') }}" class="brand" aria-label="{{ $site->site_name }}">
      <img src="{{ $logo }}" alt="{{ $site->site_name }} logo" width="42" height="42">
      <div class="brand-text"><div class="am">{{ $site->site_name_am }}</div><div class="en">{{ $site->site_name }}</div></div>
    </a>
    <div class="nav-tools">
      {{-- Desktop links --}}
      <nav class="nav-links" id="navLinks" aria-label="Main">
        @foreach ($navigation->header_links as $item)
          @if (! empty($item['children']))
            <div class="nav-item">
              <a href="{{ url($item['url'] ?? '#') }}" @class(['active' => $isActive($item['url'] ?? '')])>{{ $label($item) }}<span class="chev"></span></a>
              <div class="dropdown">
                @foreach ($item['children'] as $child)
                  <a href="{{ url($child['url'] ?? '#') }}" @class(['active' => $isActive($child['url'] ?? '')])>{{ $label($child) }}</a>
                @endforeach
              </div>
            </div>
          @else
            <a href="{{ url($item['url'] ?? '#') }}" @class(['active' => $isActive($item['url'] ?? '')])>{{ $label($item) }}</a>
          @endif
        @endforeach
        @if ($cta)<a href="{{ url($cta['url']) }}" class="btn btn-primary">{{ $cta['label'] }}</a>@endif
      </nav>

      {{-- Language: always visible, also on mobile --}}
      <div class="lang-switch" aria-label="Language">
        <a href="{{ route('locale', 'en') }}" @class(['on' => ! $am]) hreflang="en">EN</a>
        <a href="{{ route('locale', 'am') }}" @class(['on' => $am]) hreflang="am" class="eth">አማ</a>
      </div>
      <button class="menu-btn" id="menuBtn" aria-label="{{ __('site.menu') }}" aria-expanded="false" aria-controls="mMenu"><span></span></button>
    </div>
  </div>

  {{-- Mobile menu sheet --}}
  <div class="m-menu" id="mMenu">
    <div class="m-tiles">
      <a href="{{ route('home') }}" @class(['m-tile', 'active' => request()->is('/')])>{!! \App\Support\Icons::svg('mosque') !!}<span>{{ $am ? 'መነሻ' : 'Home' }}</span></a>
      @foreach ($tiles as $link)
        @continue($path($link['url'] ?? '') === '')
        <a href="{{ url($link['url'] ?? '#') }}" @class(['m-tile', 'active' => $isActive($link['url'] ?? '')])>{!! \App\Support\Icons::svg($icon($link['url'] ?? '')) !!}<span>{{ $label($link) }}</span></a>
      @endforeach
    </div>
    @if ($cta && ! $tiles->contains(fn ($l) => $path($l['url'] ?? '') === $path($cta['url'])))
      <a href="{{ url($cta['url']) }}" class="btn btn-primary m-cta">{{ $cta['label'] }}</a>
    @endif
  </div>
</header>
