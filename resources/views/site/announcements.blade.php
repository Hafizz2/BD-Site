<x-layouts.app :title="$page?->t('title')" :description="$page?->seo_description ?: $page?->t('intro')">
<x-site.page-hero :page="$page" :crumbs="[$page?->t('title') => null]" />

<section class="section">
  <div class="container" style="max-width:820px">
    @forelse ($announcements as $a)
      <article class="notice reveal" id="a-{{ $a->ulid }}">
        <time datetime="{{ $a->sent_at?->toIso8601String() }}">{{ $a->sent_at?->translatedFormat('d F Y') }}</time>
        <h3>{{ $a->t('title') }}</h3>
        @if ($img = $a->getFirstMediaUrl('image', 'card'))<img src="{{ $img }}" alt="" loading="lazy">@endif
        <div class="prose" style="margin:0;font-size:.98rem">{!! $a->t('body') !!}</div>
      </article>
    @empty
      <x-site.empty icon="chat" />
    @endforelse
    <x-site.pager :paginator="$announcements" />
  </div>
</section>
</x-layouts.app>
