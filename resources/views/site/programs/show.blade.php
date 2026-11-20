<x-layouts.app :title="$program->t('name')" :description="$program->t('summary')" :image="$program->getFirstMediaUrl('cover') ?: null">
<x-site.page-hero :title="$program->t('name')" :eyebrow="$program->type->getLabel()" :intro="$program->t('summary')"
  :image="$program->getFirstMediaUrl('cover', 'card') ?: null"
  :crumbs="[($programsPage?->t('title') ?? __('Programs')) => route('programs.index'), $program->t('name') => null]" />

<section class="section">
  <div class="container">
    <div class="split">
      <div class="prose reveal" style="margin:0">
        @if ($program->t('description')) {!! $program->t('description') !!} @else <p>{{ $program->t('summary') }}</p> @endif
      </div>
      <aside class="reveal">
        <div class="panel">
          <h3>{{ __('site.open_batches') }}</h3>
          @forelse ($batches as $batch)
            @php
              $taken = $batch->approved_count ?? 0;
              $left = $batch->capacity ? max(0, $batch->capacity - $taken) : null;
            @endphp
            <div class="batch">
              <div>
                <h4>{{ $batch->name }}</h4>
                <small>
                  @if ($batch->start_date){{ __('site.starts', ['date' => $batch->start_date->translatedFormat('d M Y')]) }}@endif
                  @if ($batch->location) · {{ $batch->location }}@endif
                </small>
                @php $sched = app()->getLocale() === 'am' ? ($batch->schedule_note_am ?: $batch->schedule_note) : $batch->schedule_note; @endphp
                @if ($sched)<small style="display:block">{{ $sched }}</small>@endif
              </div>
              @if ($left === 0)
                <span class="status closed">{{ __('site.full') }}</span>
              @else
                <div style="text-align:right">
                  <a href="{{ route('register.batch', $batch) }}" class="btn btn-primary btn-sm">{{ __('site.register_for') }}</a>
                  @if ($left !== null)<small style="display:block;margin-top:6px">{{ __('site.seats_left', ['n' => $left]) }}</small>@endif
                </div>
              @endif
            </div>
          @empty
            <p style="color:var(--muted)">{{ __('site.no_open_batches') }}</p>
            <a href="{{ $site->telegram_url ?: route('contact') }}" class="link-arrow mt-24">{{ __('site.follow_us') }} {!! \App\Support\Icons::svg('arrow', '', 2) !!}</a>
          @endforelse
        </div>
      </aside>
    </div>
  </div>
</section>

@if ($events->isNotEmpty())
<section class="section section-alt">
  <div class="container">
    <x-site.section-head :title="__('site.upcoming')" :link="route('events.index')" />
    <div class="cards">@foreach ($events as $event)<x-site.event-card :event="$event" />@endforeach</div>
  </div>
</section>
@endif

<x-site.cta />
</x-layouts.app>
