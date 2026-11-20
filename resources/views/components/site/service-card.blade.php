@props(['service', 'featured' => null])
@php $featured ??= $service->is_featured; @endphp
<a href="{{ route('services.show', $service) }}" @class(['card tilt reveal', 'featured' => $featured])>
  <div class="arch"></div>
  <div class="icon">{!! \App\Support\Icons::svg($service->icon) !!}</div>
  <h3>{{ $service->t('title') }}</h3>
  <p>{{ $service->t('summary') }}</p>
  @if ($service->t('tag'))<span class="tag">{{ $service->t('tag') }}</span>@endif
  <span class="more">{{ __('site.learn_more') }} {!! \App\Support\Icons::svg('arrow', '', 2) !!}</span>
</a>
