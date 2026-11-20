<x-layouts.app :title="$service->t('title')" :description="$service->t('summary')" :image="$service->getFirstMediaUrl('cover') ?: null">
<x-site.page-hero :title="$service->t('title')" :eyebrow="$service->t('tag')" :intro="$service->t('summary')"
  :image="$service->getFirstMediaUrl('cover', 'card') ?: null"
  :crumbs="[($servicesPage?->t('title') ?? __('Services')) => route('services.index'), $service->t('title') => null]" />

<section class="section">
  <div class="container">
    <div class="split">
      <div>
        @if ($service->t('body'))
          <div class="prose reveal" style="margin:0">{!! $service->t('body') !!}</div>
        @else
          <div class="prose reveal" style="margin:0"><p>{{ $service->t('summary') }}</p></div>
        @endif
      </div>
      <aside class="reveal">
        @if ($service->program)
          @php $program = $service->program; $open = $program->openBatches()->get(); @endphp
          <div class="panel dark">
            <h3>{{ __('site.related_program') }}</h3>
            <div class="info-list">
              <div class="row"><span class="ic">{!! \App\Support\Icons::svg($program->icon ?: $service->icon) !!}</span><div><small>{{ $program->type->getLabel() }}</small><b>{{ $program->t('name') }}</b></div></div>
              @forelse ($open as $batch)
                <div class="row"><span class="ic">{!! \App\Support\Icons::svg('calendar') !!}</span><div><small>{{ __('site.registration_open') }}</small><b>{{ $batch->name }}</b>@if($batch->start_date) · {{ __('site.starts', ['date' => $batch->start_date->translatedFormat('d M Y')]) }}@endif</div></div>
              @empty
                <p style="color:rgba(255,255,255,.7);font-size:.92rem">{{ __('site.no_open_batches') }}</p>
              @endforelse
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px">
              @if ($open->count() === 1)<a href="{{ route('register.batch', $open->first()) }}" class="btn btn-gold btn-sm">{{ __('site.register_for') }}</a>@endif
              <a href="{{ route('programs.show', $program) }}" class="btn btn-sm" style="color:#fff;border:1px solid rgba(255,255,255,.3)">{{ __('site.learn_more') }}</a>
            </div>
          </div>
        @else
          <div class="panel">
            <h3>{{ __('site.contact_us') }}</h3>
            <p style="color:var(--muted)">{{ $site->t('tagline') }}</p>
            <a href="{{ route('contact') }}" class="btn btn-primary btn-sm mt-24">{{ __('site.contact_us') }}</a>
          </div>
        @endif
      </aside>
    </div>
  </div>
</section>

@if ($others->isNotEmpty())
<section class="section services">
  <div class="container">
    <x-site.section-head :title="__('site.other_services')" :link="route('services.index')" />
    <div class="grid">
      @foreach ($others as $s)<x-site.service-card :service="$s" :featured="false" />@endforeach
    </div>
  </div>
</section>
@endif

<x-site.cta />
</x-layouts.app>
