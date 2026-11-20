@props(['article'])
@php $cover = $article->getFirstMediaUrl('cover', 'card'); @endphp
<a href="{{ route('articles.show', $article) }}" class="mcard reveal">
  <div class="thumb">
    @if ($cover)
      <img src="{{ $cover }}" alt="{{ $article->t('title') }}" loading="lazy">
    @else
      <div class="pattern"></div><div class="ph">{!! \App\Support\Icons::svg('pen') !!}</div>
    @endif
    @if ($article->category)<span class="badge">{{ $article->category->t('name') }}</span>@endif
  </div>
  <div class="body">
    <h3>{{ $article->t('title') }}</h3>
    @if ($article->t('excerpt'))<p>{{ \Illuminate\Support\Str::limit($article->t('excerpt'), 140) }}</p>@endif
    <div class="foot-meta">
      <span>{!! \App\Support\Icons::svg('calendar') !!} {{ $article->published_at?->translatedFormat('d M Y') }}</span>
      <span>{!! \App\Support\Icons::svg('clock') !!} {{ __('site.min_read', ['n' => $article->readingMinutes()]) }}</span>
    </div>
  </div>
</a>
