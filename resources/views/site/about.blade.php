<x-layouts.app :title="$page?->t('title')" :description="$page?->seo_description ?: $page?->t('intro')">
<x-site.page-hero :page="$page" :crumbs="[$page?->t('title') => null]" />

@if ($page?->t('body'))
<section class="section">
  <div class="container"><div class="prose reveal">{!! $page->t('body') !!}</div></div>
</section>
@endif

<section class="section tight">
  <div class="container">
    <div class="about-grid">
      @php $am = app()->getLocale() === 'am'; @endphp
      @foreach ($homepage->about_cards as $i => $card)
        <div class="about-card reveal">
          <span class="num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
          <h3>{{ $am && filled($card['title_am'] ?? null) ? $card['title_am'] : ($card['title'] ?? '') }}</h3>
          <p>{{ $am && filled($card['text_am'] ?? null) ? $card['text_am'] : ($card['text'] ?? '') }}</p>
          @if ($loop->last && $homepage->pillars)
            <div class="pillars">@foreach ($homepage->pillars as $pill)<span class="pill">{{ $am && filled($pill['label_am'] ?? null) ? $pill['label_am'] : ($pill['label'] ?? '') }}</span>@endforeach</div>
          @endif
        </div>
      @endforeach
    </div>
  </div>
</section>

<x-site.verse />

@if ($stats->isNotEmpty())
<section class="section">
  <div class="container">
    <div class="stats" style="grid-template-columns:repeat({{ min(4, $stats->count()) }},1fr);max-width:820px;margin:0 auto">
      @foreach ($stats->take(4) as $stat)
        <div class="stat reveal"><b data-count="{{ $stat->resolvedValue() }}" data-suffix="{{ $stat->suffix }}">0</b><span>{{ $stat->t('label') }}</span></div>
      @endforeach
    </div>
  </div>
</section>
@endif

@if ($steps->isNotEmpty())
<section class="section section-alt journey">
  <div class="container">
    <x-site.section-head :eyebrow="$homepage->section('journey', 'eyebrow')" :title="$homepage->section('journey', 'title')" />
    <div class="steps">
      @foreach ($steps as $step)
        <div class="step reveal"><span class="dot"></span><h4>{{ $step->t('title') }}</h4><p>{{ $step->t('description') }}</p></div>
      @endforeach
    </div>
  </div>
</section>
@endif

<x-site.cta />
</x-layouts.app>
