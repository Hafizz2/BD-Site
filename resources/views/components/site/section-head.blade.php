@props(['eyebrow' => null, 'title', 'intro' => null, 'link' => null, 'linkLabel' => null])
@if ($link)
  <div class="head-row reveal">
    <div class="head">
      @if ($eyebrow)<span class="eyebrow">{{ $eyebrow }}</span>@endif
      <h2>{{ $title }}</h2>
      <div class="divider"><i></i><b></b><i></i></div>
      @if ($intro)<p>{{ $intro }}</p>@endif
    </div>
    <a href="{{ $link }}" class="link-arrow">{{ $linkLabel ?? __('site.view_all') }} {!! \App\Support\Icons::svg('arrow', '', 2) !!}</a>
  </div>
@else
  <div class="head reveal">
    @if ($eyebrow)<span class="eyebrow">{{ $eyebrow }}</span>@endif
    <h2>{{ $title }}</h2>
    <div class="divider"><i></i><b></b><i></i></div>
    @if ($intro)<p>{{ $intro }}</p>@endif
  </div>
@endif
