<x-layouts.app :title="$page?->t('title')" :description="$page?->seo_description ?: $page?->t('intro')">
<x-site.page-hero :page="$page" :crumbs="[$page?->t('title') => null]" />

<section class="section">
  <div class="container">
    @if ($page?->t('body'))<div class="prose reveal" style="margin-bottom:40px">{!! $page->t('body') !!}</div>@endif
    @if ($goals->isEmpty())
      <x-site.empty icon="target" />
    @else
      <div class="grid two">
        @foreach ($goals as $goal)
          @include('site.partials.goal', ['goal' => $goal, 'n' => $loop->iteration])
        @endforeach
      </div>
    @endif
  </div>
</section>

<x-site.cta />
</x-layouts.app>
