<div class="goal reveal">
  <span class="num">{{ str_pad($n, 2, '0', STR_PAD_LEFT) }}</span>
  <div class="icon">{!! \App\Support\Icons::svg($goal->icon) !!}</div>
  <h3>{{ $goal->t('title') }}</h3>
  <p>{{ $goal->t('description') }}</p>
  @if ($goal->progress !== null)
    <div class="progress"><i data-progress="{{ $goal->progress }}"></i></div>
    <div class="progress-label"><span>{{ $goal->t('horizon') }}</span><span>{{ $goal->progress }}%</span></div>
  @elseif ($goal->t('horizon'))
    <span class="horizon">{{ $goal->t('horizon') }}</span>
  @endif
</div>
