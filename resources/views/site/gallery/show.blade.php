<x-layouts.app :title="$album->t('title')" :description="$album->t('description')" :image="$album->getFirstMediaUrl('photos', 'large') ?: null">
<x-site.page-hero :title="$album->t('title')" :intro="$album->t('description')" :eyebrow="$album->taken_on?->translatedFormat('F Y')"
  :crumbs="[($galleryPage?->t('title') ?? __('Gallery')) => route('gallery.index'), $album->t('title') => null]" />

<section class="section">
  <div class="container">
    @if ($photos->isEmpty())
      <x-site.empty />
    @else
      <div class="photos">
        @foreach ($photos as $photo)
          <a href="{{ $photo->getUrl('large') }}" data-lightbox data-alt="{{ $photo->getCustomProperty('caption') ?? $album->t('title') }}">
            <img src="{{ $photo->getUrl('thumb') }}" alt="{{ $photo->getCustomProperty('caption') ?? $album->t('title') }}" loading="lazy">
          </a>
        @endforeach
      </div>
    @endif
  </div>
</section>

<div class="lightbox" id="lightbox" role="dialog" aria-modal="true">
  <button class="lb-close" aria-label="Close">×</button>
  <button class="lb-prev" aria-label="Previous">‹</button>
  <img src="" alt="">
  <button class="lb-next" aria-label="Next">›</button>
</div>
</x-layouts.app>
