<x-layouts.app :transparent-nav="true">
@php
    $logo = $site->logo_path ? asset('storage/'.$site->logo_path) : asset('images/pin.png');
    $sec = fn ($key, $field) => $homepage->section($key, $field);
    $gradients = ['g1', 'g2', 'g3', 'g4', 'g5'];
@endphp

<!-- HERO -->
<section class="hero">
  <div class="pattern pattern-layer"></div>
  <div class="container hero-grid">
    <div class="stage" id="stage">
      <div class="glow"></div>
      <div class="ring"></div>
      <div class="ring r2"></div>
      <div class="logo3d" id="logo3d"><img src="{{ $logo }}" alt="" width="300" height="300" fetchpriority="high"></div>
      <div class="shadow-oval"></div>
    </div>
    <div>
      @if ($homepage->t('hero_eyebrow'))<span class="eyebrow">{{ $homepage->t('hero_eyebrow') }}</span>@endif
      <h1>{{ $homepage->hero_title }} @if ($homepage->hero_title_highlight)<em>{{ $homepage->hero_title_highlight }}</em>@endif</h1>
      @if ($homepage->hero_title_am)<div class="am-title">{{ $homepage->hero_title_am }}</div>@endif
      <p class="lead">{{ $homepage->t('hero_lead') }}</p>
      <div class="hero-cta">
        @if ($homepage->hero_primary_url)
          <a href="{{ url($homepage->hero_primary_url) }}" class="btn btn-primary">{{ $homepage->t('hero_primary_label') }} {!! \App\Support\Icons::svg('arrow', '', 2) !!}</a>
        @endif
        @if ($homepage->hero_secondary_url)
          <a href="{{ url($homepage->hero_secondary_url) }}" class="btn btn-ghost">{{ $homepage->t('hero_secondary_label') }}</a>
        @endif
      </div>
      @if ($homepage->show_stats && $stats->isNotEmpty())
        <div class="stats" style="grid-template-columns:repeat({{ min(4, $stats->count()) }},1fr)">
          @foreach ($stats->take(4) as $stat)
            <div class="stat"><b data-count="{{ $stat->resolvedValue() }}" data-suffix="{{ $stat->suffix }}">0</b><span>{{ $stat->t('label') }}</span></div>
          @endforeach
        </div>
      @endif
    </div>
  </div>
</section>

@if ($homepage->show_verse)
  <x-site.verse />
@endif

<!-- ABOUT -->
@if ($homepage->enabled('about'))
<section class="section" id="about">
  <div class="container">
    <x-site.section-head :eyebrow="$sec('about', 'eyebrow')" :title="$sec('about', 'title')" :intro="$sec('about', 'intro')" />
    <div class="about-grid">
      @foreach ($homepage->about_cards as $i => $card)
        @php $am = app()->getLocale() === 'am'; @endphp
        <div class="about-card reveal">
          <span class="num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
          <h3>{{ $am && filled($card['title_am'] ?? null) ? $card['title_am'] : ($card['title'] ?? '') }}</h3>
          <p>{{ $am && filled($card['text_am'] ?? null) ? $card['text_am'] : ($card['text'] ?? '') }}</p>
          @if ($loop->last && $homepage->pillars)
            <div class="pillars">
              @foreach ($homepage->pillars as $pill)
                <span class="pill">{{ $am && filled($pill['label_am'] ?? null) ? $pill['label_am'] : ($pill['label'] ?? '') }}</span>
              @endforeach
            </div>
          @endif
        </div>
      @endforeach
    </div>
    <div class="center mt-36 reveal"><a href="{{ route('about') }}" class="link-arrow">{{ __('site.learn_more') }} {!! \App\Support\Icons::svg('arrow', '', 2) !!}</a></div>
  </div>
</section>
@endif

<!-- SERVICES -->
@if ($homepage->enabled('services') && $services->isNotEmpty())
<section class="section services" id="services">
  <div class="container">
    <x-site.section-head :eyebrow="$sec('services', 'eyebrow')" :title="$sec('services', 'title')" :intro="$sec('services', 'intro')" />
    <div class="grid">
      @foreach ($services as $service)
        <x-site.service-card :service="$service" :featured="$loop->first && $service->is_featured" />
      @endforeach
    </div>
    <div class="center mt-36 reveal"><a href="{{ route('services.index') }}" class="btn btn-ghost">{{ __('site.view_all') }} {!! \App\Support\Icons::svg('arrow', '', 2) !!}</a></div>
  </div>
</section>
@endif

<!-- GOALS -->
@if ($homepage->enabled('goals') && $goals->isNotEmpty())
<section class="section" id="goals">
  <div class="container">
    <x-site.section-head :eyebrow="$sec('goals', 'eyebrow')" :title="$sec('goals', 'title')" :intro="$sec('goals', 'intro')" :link="route('goals')" />
    <div class="grid two">
      @foreach ($goals as $goal)
        @include('site.partials.goal', ['goal' => $goal, 'n' => $loop->iteration])
      @endforeach
    </div>
  </div>
</section>
@endif

<!-- JOURNEY -->
@if ($homepage->enabled('journey') && $steps->isNotEmpty())
<section class="section journey section-alt" id="journey">
  <div class="container">
    <x-site.section-head :eyebrow="$sec('journey', 'eyebrow')" :title="$sec('journey', 'title')" :intro="$sec('journey', 'intro')" />
    <div class="steps">
      @foreach ($steps as $step)
        <div class="step reveal"><span class="dot"></span><h4>{{ $step->t('title') }}</h4><p>{{ $step->t('description') }}</p></div>
      @endforeach
    </div>
  </div>
</section>
@endif

<!-- LATEST ARTICLES -->
@if ($homepage->enabled('articles') && $articles->isNotEmpty())
<section class="section" id="articles">
  <div class="container">
    <x-site.section-head :eyebrow="$sec('articles', 'eyebrow')" :title="$sec('articles', 'title')" :intro="$sec('articles', 'intro')" :link="route('articles.index')" />
    <div class="cards">
      @foreach ($articles as $article)
        <x-site.article-card :article="$article" />
      @endforeach
    </div>
  </div>
</section>
@endif

<!-- EVENTS -->
@if ($homepage->enabled('events') && $events->isNotEmpty())
<section class="section section-alt" id="events">
  <div class="container">
    <x-site.section-head :eyebrow="$sec('events', 'eyebrow')" :title="$sec('events', 'title')" :intro="$sec('events', 'intro')" :link="route('events.index')" />
    <div class="cards">
      @foreach ($events as $event)
        <x-site.event-card :event="$event" />
      @endforeach
    </div>
  </div>
</section>
@endif

<!-- GALLERY -->
@if ($homepage->enabled('gallery') && $albums->isNotEmpty())
<section class="section" id="gallery">
  <div class="container">
    <x-site.section-head :eyebrow="$sec('gallery', 'eyebrow')" :title="$sec('gallery', 'title')" :intro="$sec('gallery', 'intro')" :link="route('gallery.index')" />
    <div class="gallery reveal">
      @foreach ($albums as $album)
        @php $img = $album->getFirstMediaUrl('photos', 'thumb'); @endphp
        <a href="{{ route('gallery.show', $album) }}" @class(['g', $gradients[$loop->index % 5], 'tall' => $loop->first])
           @if ($img) style="--bg:url('{{ $img }}')" @endif>
          <span>{{ $album->t('title') }} @if ($album->taken_on)<small>{{ $album->taken_on->translatedFormat('M Y') }}</small>@endif</span>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif

<x-site.cta />
</x-layouts.app>
