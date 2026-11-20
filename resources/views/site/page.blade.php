<x-layouts.app :title="$page->seo_title ?: $page->t('title')" :description="$page->seo_description ?: $page->t('intro')" :image="$page->getFirstMediaUrl('cover', 'wide') ?: null">
<x-site.page-hero :page="$page" :crumbs="[$page->t('title') => null]" />
<section class="section">
  <div class="container"><div class="prose reveal">{!! $page->t('body') !!}</div></div>
</section>
</x-layouts.app>
