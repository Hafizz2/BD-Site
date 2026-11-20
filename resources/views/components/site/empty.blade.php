@props(['icon' => 'star'])
<div class="empty reveal">
  {!! \App\Support\Icons::svg($icon) !!}
  <p>{{ $slot->isEmpty() ? __('site.nothing_here') : $slot }}</p>
</div>
