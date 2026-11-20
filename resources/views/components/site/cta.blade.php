@if ($homepage->show_cta)
<section id="register" class="section tight">
  <div class="cta reveal">
    <div class="pattern pattern-layer"></div>
    <h2>{{ $homepage->t('cta_title') }}</h2>
    <p>{{ $homepage->t('cta_text') }}</p>
    <div class="row">
      @if ($homepage->cta_primary_url)<a href="{{ url($homepage->cta_primary_url) }}" class="btn btn-primary">{{ $homepage->t('cta_primary_label') }}</a>@endif
      @if ($homepage->cta_secondary_url)<a href="{{ url($homepage->cta_secondary_url) }}" class="btn btn-ghost">{{ $homepage->t('cta_secondary_label') }}</a>@endif
    </div>
  </div>
</section>
@endif
