<x-layouts.app :title="$event->t('title')" :description="$event->t('summary')" :image="$event->getFirstMediaUrl('cover') ?: null">
@push('head')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org', '@type' => 'Event', 'name' => $event->t('title'),
    'startDate' => $event->starts_at->toIso8601String(), 'endDate' => $event->ends_at?->toIso8601String(),
    'location' => ['@type' => 'Place', 'name' => $event->t('location') ?: $site->address, 'address' => $site->address],
    'organizer' => ['@type' => 'Organization', 'name' => $site->site_name, 'url' => url('/')],
    'image' => $event->getFirstMediaUrl('cover') ?: null, 'description' => $event->t('summary'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
<x-site.page-hero :title="$event->t('title')" :intro="$event->t('summary')" :image="$event->getFirstMediaUrl('cover', 'card') ?: null"
  :crumbs="[($eventsPage?->t('title') ?? __('Events')) => route('events.index'), \Illuminate\Support\Str::limit($event->t('title'), 40) => null]">
  <div class="meta-row">
    <span>{!! \App\Support\Icons::svg('calendar') !!} {{ $event->starts_at->translatedFormat('l, d F Y') }}</span>
    <span>{!! \App\Support\Icons::svg('clock') !!} {{ $event->starts_at->format('H:i') }}@if($event->ends_at) – {{ $event->ends_at->format('H:i') }}@endif</span>
    @if ($event->t('location'))<span>{!! \App\Support\Icons::svg('map') !!} {{ $event->t('location') }}</span>@endif
  </div>
</x-site.page-hero>

<section class="section">
  <div class="container">
    <div class="split">
      <div class="prose reveal" style="margin:0">
        @if ($event->t('description')) {!! $event->t('description') !!} @else <p>{{ $event->t('summary') }}</p> @endif
      </div>
      <aside class="reveal">
        <div class="panel dark">
          <div class="info-list">
            <div class="row"><span class="ic">{!! \App\Support\Icons::svg('calendar') !!}</span><div><small>{{ __('site.when') }}</small><b>{{ $event->starts_at->translatedFormat('D, d M Y · H:i') }}</b></div></div>
            @if ($event->t('location'))<div class="row"><span class="ic">{!! \App\Support\Icons::svg('map') !!}</span><div><small>{{ __('site.where') }}</small><b>{{ $event->t('location') }}</b></div></div>@endif
            @if ($event->program)<div class="row"><span class="ic">{!! \App\Support\Icons::svg('graduation') !!}</span><div><small>{{ __('site.related_program') }}</small><b><a href="{{ route('programs.show', $event->program) }}">{{ $event->program->t('name') }}</a></b></div></div>@endif
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:22px">
            @if ($event->registration_url && $event->starts_at->isFuture())
              <a href="{{ $event->registration_url }}" class="btn btn-gold btn-sm" target="_blank" rel="noopener">{{ __('site.event_register') }}</a>
            @endif
            <a href="{{ route('events.ics', $event) }}" class="btn btn-sm" style="color:#fff;border:1px solid rgba(255,255,255,.3)">{{ __('site.add_to_calendar') }}</a>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>

@if ($more->isNotEmpty())
<section class="section section-alt">
  <div class="container">
    <x-site.section-head :title="__('site.upcoming')" :link="route('events.index')" />
    <div class="cards">@foreach ($more as $e)<x-site.event-card :event="$e" />@endforeach</div>
  </div>
</section>
@endif
</x-layouts.app>
