<x-layouts.app :title="$page?->t('title')" :description="$page?->seo_description ?: $page?->t('intro')">
<x-site.page-hero :page="$page" :crumbs="[$page?->t('title') => null]" />

<section class="section services">
  <div class="container">
    @if ($services->isEmpty())
      <x-site.empty />
    @else
      <div class="grid">
        @foreach ($services as $service)
          <x-site.service-card :service="$service" />
        @endforeach
      </div>
    @endif
  </div>
</section>

@if ($page?->t('body'))
<section class="section tight"><div class="container"><div class="prose reveal">{!! $page->t('body') !!}</div></div></section>
@endif

<x-site.cta />
</x-layouts.app>
