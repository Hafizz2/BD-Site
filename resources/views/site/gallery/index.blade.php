<x-layouts.app :title="$page?->t('title')" :description="$page?->seo_description ?: $page?->t('intro')">
<x-site.page-hero :page="$page" :crumbs="[$page?->t('title') => null]" />

<section class="section">
  <div class="container">
    @if ($albums->isEmpty())
      <x-site.empty />
    @else
      <div class="cards">@foreach ($albums as $album)<x-site.album-card :album="$album" />@endforeach</div>
      <x-site.pager :paginator="$albums" />
    @endif
  </div>
</section>
</x-layouts.app>
