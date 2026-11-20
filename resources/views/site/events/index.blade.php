<x-layouts.app :title="$page?->t('title')" :description="$page?->seo_description ?: $page?->t('intro')">
<x-site.page-hero :page="$page" :crumbs="[$page?->t('title') => null]" />

<section class="section">
  <div class="container">
    <x-site.section-head :title="__('site.upcoming')" />
    @if ($upcoming->isEmpty())
      <x-site.empty icon="calendar">{{ __('site.no_upcoming') }}</x-site.empty>
    @else
      <div class="cards">@foreach ($upcoming as $event)<x-site.event-card :event="$event" />@endforeach</div>
    @endif
  </div>
</section>

@if ($past->isNotEmpty())
<section class="section section-alt">
  <div class="container">
    <x-site.section-head :title="__('site.past_events')" />
    <div class="cards">@foreach ($past as $event)<x-site.event-card :event="$event" />@endforeach</div>
    <x-site.pager :paginator="$past" />
  </div>
</section>
@endif
</x-layouts.app>
