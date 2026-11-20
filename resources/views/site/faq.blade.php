<x-layouts.app :title="$page?->t('title')" :description="$page?->seo_description ?: $page?->t('intro')">
@push('head')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org', '@type' => 'FAQPage',
    'mainEntity' => $faqs->map(fn ($f) => ['@type' => 'Question', 'name' => $f->t('question'), 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f->t('answer'))]])->values(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
<x-site.page-hero :page="$page" :crumbs="[$page?->t('title') => null]" />

<section class="section">
  <div class="container">
    @if ($faqs->isEmpty())
      <x-site.empty icon="chat" />
    @else
      <div class="faq">
        @foreach ($faqs as $faq)
          <details class="reveal" @if($loop->first) open @endif>
            <summary>{{ $faq->t('question') }}</summary>
            <div class="answer">{!! nl2br(e($faq->t('answer'))) !!}</div>
          </details>
        @endforeach
      </div>
    @endif
    <div class="center mt-36 reveal"><a href="{{ route('contact') }}" class="btn btn-ghost">{{ __('site.contact_us') }}</a></div>
  </div>
</section>
</x-layouts.app>
