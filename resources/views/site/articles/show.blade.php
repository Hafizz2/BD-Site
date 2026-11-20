<x-layouts.app :title="$article->seo_title ?: $article->t('title')" :description="$article->seo_description ?: $article->t('excerpt')" :image="$article->getFirstMediaUrl('cover', 'wide') ?: null" og-type="article">
@push('head')
<meta property="article:published_time" content="{{ $article->published_at?->toIso8601String() }}">
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org', '@type' => 'Article',
    'headline' => $article->t('title'), 'datePublished' => $article->published_at?->toIso8601String(),
    'dateModified' => $article->updated_at?->toIso8601String(), 'image' => $article->getFirstMediaUrl('cover', 'wide') ?: null,
    'author' => ['@type' => 'Person', 'name' => $article->authorName() ?: $site->site_name],
    'publisher' => ['@type' => 'Organization', 'name' => $site->site_name],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

<x-site.page-hero :title="$article->t('title')" :eyebrow="$article->category?->t('name')"
  :crumbs="[($articlesPage?->t('title') ?? __('Articles')) => route('articles.index'), \Illuminate\Support\Str::limit($article->t('title'), 40) => null]">
  <div class="meta-row">
    @if ($article->authorName())<span>{!! \App\Support\Icons::svg('pen') !!} {{ __('site.by', ['name' => $article->authorName()]) }}</span>@endif
    <span>{!! \App\Support\Icons::svg('calendar') !!} {{ $article->published_at?->translatedFormat('d F Y') }}</span>
    <span>{!! \App\Support\Icons::svg('clock') !!} {{ __('site.min_read', ['n' => $article->readingMinutes()]) }}</span>
  </div>
</x-site.page-hero>

<section class="section" style="padding-top:0">
  <div class="container">
    @if ($cover = $article->getFirstMediaUrl('cover', 'wide'))
      <div class="article-cover reveal"><img src="{{ $cover }}" alt="{{ $article->t('title') }}"></div>
    @endif
    @if ($article->t('excerpt'))<p class="prose reveal" style="font-size:1.2rem;color:var(--green-800);font-weight:500">{{ $article->t('excerpt') }}</p>@endif
    <article class="prose reveal" style="margin-top:24px">{!! $article->t('body') !!}</article>

    @php $shareUrl = urlencode(url()->current()); $shareText = urlencode($article->t('title')); @endphp
    <div class="share">
      <span>{{ __('site.share') }}:</span>
      <a href="https://t.me/share/url?url={{ $shareUrl }}&text={{ $shareText }}" target="_blank" rel="noopener" aria-label="Telegram"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.9 4.3 18.7 19.4c-.2 1-.9 1.3-1.7.8l-4.8-3.5-2.3 2.2c-.3.3-.5.5-1 .5l.3-4.9 8.9-8c.4-.3-.1-.5-.6-.2l-11 6.9-4.7-1.5c-1-.3-1-1 .2-1.5L20.6 3c.9-.3 1.6.2 1.3 1.3z"/></svg></a>
      <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H8v4h2v8h4v-8h3l1-4h-4V8z"/></svg></a>
      <a href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}" target="_blank" rel="noopener" aria-label="WhatsApp">{!! \App\Support\Icons::svg('chat') !!}</a>
    </div>
  </div>
</section>

@if ($related->isNotEmpty())
<section class="section section-alt">
  <div class="container">
    <x-site.section-head :title="__('site.related_articles')" :link="route('articles.index')" />
    <div class="cards">@foreach ($related as $r)<x-site.article-card :article="$r" />@endforeach</div>
  </div>
</section>
@endif
</x-layouts.app>
