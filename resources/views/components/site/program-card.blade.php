@props(['program'])
@php
    $cover = $program->getFirstMediaUrl('cover', 'card');
    $open = $program->open_batches_count ?? $program->openBatches()->count();
@endphp
<a href="{{ route('programs.show', $program) }}" class="mcard reveal">
  <div class="thumb">
    @if ($cover)
      <img src="{{ $cover }}" alt="{{ $program->t('name') }}" loading="lazy">
    @else
      <div class="pattern"></div><div class="ph">{!! \App\Support\Icons::svg($program->icon ?: 'graduation') !!}</div>
    @endif
    <span @class(['badge', 'gold' => $open])>{{ $open ? __('site.registration_open') : $program->type->getLabel() }}</span>
  </div>
  <div class="body">
    <span class="kicker">{{ $program->type->getLabel() }}</span>
    <h3>{{ $program->t('name') }}</h3>
    @if ($program->t('summary'))<p>{{ \Illuminate\Support\Str::limit($program->t('summary'), 140) }}</p>@endif
    <div class="foot-meta"><span class="link-arrow">{{ __('site.learn_more') }} {!! \App\Support\Icons::svg('arrow', '', 2) !!}</span></div>
  </div>
</a>
