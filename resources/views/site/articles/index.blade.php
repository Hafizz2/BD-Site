<x-layouts.app :title="$category ? $category->t('name').' — '.$page?->t('title') : $page?->t('title')" :description="$page?->seo_description ?: $page?->t('intro')">
<x-site.page-hero :page="$page" :crumbs="$category ? [$page?->t('title') => route('articles.index'), $category->t('name') => null] : [$page?->t('title') => null]">
  <form class="search" action="{{ route('articles.index') }}" method="get" role="search">
    <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('site.search_articles') }}" aria-label="{{ __('site.search') }}">
    <button class="btn btn-primary btn-sm" type="submit">{{ __('site.search') }}</button>
  </form>
</x-site.page-hero>

<section class="section">
  <div class="container">
    @if ($categories->isNotEmpty())
      <div class="chips reveal">
        <a href="{{ route('articles.index') }}" @class(['chip', 'on' => ! $category])>{{ __('site.all') }}</a>
        @foreach ($categories as $c)
          <a href="{{ route('articles.category', $c) }}" @class(['chip', 'on' => $category?->is($c)])>{{ $c->t('name') }} <span style="opacity:.6">· {{ $c->articles_count }}</span></a>
        @endforeach
      </div>
    @endif

    @if ($featured)
      @php $cover = $featured->getFirstMediaUrl('cover', 'wide'); @endphp
      <a href="{{ route('articles.show', $featured) }}" class="feature reveal">
        <div class="thumb">@if ($cover)<img src="{{ $cover }}" alt="{{ $featured->t('title') }}">@endif</div>
        <div class="body">
          <div class="pattern"></div>
          <span class="kicker">{{ __('site.featured') }} @if($featured->category) · {{ $featured->category->t('name') }}@endif</span>
          <h2>{{ $featured->t('title') }}</h2>
          @if ($featured->t('excerpt'))<p>{{ \Illuminate\Support\Str::limit($featured->t('excerpt'), 200) }}</p>@endif
          <span class="link-arrow" style="color:var(--gold)">{{ __('site.read_more') }} {!! \App\Support\Icons::svg('arrow', '', 2) !!}</span>
        </div>
      </a>
    @endif

    @if ($articles->isEmpty() && ! $featured)
      <x-site.empty icon="pen">{{ $q ? __('site.no_results', ['q' => $q]) : '' }}</x-site.empty>
    @else
      <div class="cards">
        @foreach ($articles as $article)<x-site.article-card :article="$article" />@endforeach
      </div>
      <x-site.pager :paginator="$articles" />
    @endif
  </div>
</section>
</x-layouts.app>
