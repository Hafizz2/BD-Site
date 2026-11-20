@props(['album'])
@php
    $cover = $album->getFirstMediaUrl('photos', 'thumb');
    $count = $album->media_count ?? $album->getMedia('photos')->count();
@endphp
<a href="{{ route('gallery.show', $album) }}" class="mcard reveal">
  <div class="thumb">
    @if ($cover)
      <img src="{{ $cover }}" alt="{{ $album->t('title') }}" loading="lazy">
    @else
      <div class="pattern"></div><div class="ph">{!! \App\Support\Icons::svg('star') !!}</div>
    @endif
    <span class="badge">{{ __('site.photos', ['n' => $count]) }}</span>
  </div>
  <div class="body">
    <h3>{{ $album->t('title') }}</h3>
    @if ($album->taken_on)<div class="foot-meta"><span>{!! \App\Support\Icons::svg('calendar') !!} {{ $album->taken_on->translatedFormat('M Y') }}</span></div>@endif
  </div>
</a>
