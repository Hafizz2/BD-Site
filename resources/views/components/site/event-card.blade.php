@props(['event'])
@php $cover = $event->getFirstMediaUrl('cover', 'card'); @endphp
<a href="{{ route('events.show', $event) }}" class="mcard reveal">
  <div class="thumb">
    @if ($cover)
      <img src="{{ $cover }}" alt="{{ $event->t('title') }}" loading="lazy">
    @else
      <div class="pattern"></div><div class="ph">{!! \App\Support\Icons::svg('calendar') !!}</div>
    @endif
    @if ($event->starts_at->isFuture())<span class="badge gold">{{ __('site.upcoming') }}</span>@endif
    <div class="date-block"><b>{{ $event->starts_at->format('d') }}</b><small>{{ $event->starts_at->translatedFormat('M') }}</small></div>
  </div>
  <div class="body">
    <h3>{{ $event->t('title') }}</h3>
    @if ($event->t('summary'))<p>{{ \Illuminate\Support\Str::limit($event->t('summary'), 130) }}</p>@endif
    <div class="foot-meta">
      <span>{!! \App\Support\Icons::svg('clock') !!} {{ $event->starts_at->translatedFormat('D, d M · H:i') }}</span>
      @if ($event->t('location'))<span>{!! \App\Support\Icons::svg('map') !!} {{ $event->t('location') }}</span>@endif
    </div>
  </div>
</a>
