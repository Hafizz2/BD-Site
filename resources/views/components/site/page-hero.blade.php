@props(['page' => null, 'title' => null, 'eyebrow' => null, 'intro' => null, 'image' => null, 'crumbs' => []])
@php
    $title ??= $page?->t('title');
    $eyebrow ??= $page?->t('eyebrow');
    $intro ??= $page?->t('intro');
    $image ??= $page?->getFirstMediaUrl('cover', 'wide') ?: null;
@endphp
<section @class(['page-hero', 'has-image' => $image]) @if($image) style="--hero-img:url('{{ $image }}')" @endif>
  <div class="pattern pattern-layer"></div>
  <div class="container">
    @if ($crumbs)
      <nav class="crumbs" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">{{ __('site.home') }}</a>
        @foreach ($crumbs as $label => $url)
          <span class="sep">/</span>
          @if ($url)<a href="{{ $url }}">{{ $label }}</a>@else<span>{{ $label }}</span>@endif
        @endforeach
      </nav>
    @endif
    @if ($eyebrow)<span class="eyebrow">{{ $eyebrow }}</span>@endif
    <h1>{{ $title }}</h1>
    <div class="divider"><i></i><b></b><i></i></div>
    @if ($intro)<p>{{ $intro }}</p>@endif
    {{ $slot }}
  </div>
</section>
