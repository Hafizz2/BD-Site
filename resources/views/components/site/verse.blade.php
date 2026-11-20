@if ($site->verse_arabic)
<div class="verse">
  <div class="pattern pattern-layer"></div>
  <div class="container">
    <div class="ar" lang="ar">{{ $site->verse_arabic }}</div>
    @if ($site->t('verse_translation'))<p class="tr">{{ $site->t('verse_translation') }}</p>@endif
    @if ($site->t('verse_reference'))<span class="ref">{{ $site->t('verse_reference') }}</span>@endif
  </div>
</div>
@endif
