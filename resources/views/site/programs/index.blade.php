<x-layouts.app :title="$page?->t('title')" :description="$page?->seo_description ?: $page?->t('intro')">
<x-site.page-hero :page="$page" :crumbs="[$page?->t('title') => null]" />

<section class="section">
  <div class="container">
    @if ($programs->isEmpty())
      <x-site.empty icon="graduation" />
    @else
      <div class="cards">
        @foreach ($programs as $program)<x-site.program-card :program="$program" />@endforeach
      </div>
    @endif
  </div>
</section>

<x-site.cta />
</x-layouts.app>
