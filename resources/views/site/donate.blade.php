<x-layouts.app :title="$page?->t('title')" :description="$page?->seo_description ?: $page?->t('intro')">
<x-site.page-hero :page="$page" :crumbs="[$page?->t('title') => null]" />

<section class="section">
  <div class="container">
    @php $am = app()->getLocale() === 'am'; @endphp
    @if ($am ? ($payment->donate_intro_am ?: $payment->donate_intro) : $payment->donate_intro)
      <p class="prose reveal center" style="font-size:1.15rem;color:var(--green-800);margin-bottom:36px">{{ $am ? ($payment->donate_intro_am ?: $payment->donate_intro) : $payment->donate_intro }}</p>
    @endif

    <x-site.section-head :title="__('site.donate_accounts')" />
    <div class="accounts">
      @foreach ($accounts as $acc)
        <div class="account reveal">
          <div class="pattern"></div>
          <small>{{ \App\Enums\PaymentMethod::tryFrom($acc['method'] ?? '')?->getLabel() ?? ($acc['method'] ?? '') }}</small>
          <h4>{{ $acc['bank_name'] ?? '' }}</h4>
          <div style="color:rgba(255,255,255,.75);font-size:.92rem">{{ $acc['account_name'] ?? '' }}</div>
          @php $note = $am ? ($acc['note_am'] ?? $acc['note'] ?? null) : ($acc['note'] ?? null); @endphp
          @if ($note)<div style="color:rgba(255,255,255,.6);font-size:.85rem;margin-top:6px">{{ $note }}</div>@endif
          <div class="number">
            <span>{{ $acc['account_number'] ?? '' }}</span>
            <button type="button" class="copy-btn" data-copy="{{ $acc['account_number'] ?? '' }}" data-done="{{ __('site.copied') }}">{{ __('site.copy') }}</button>
          </div>
        </div>
      @endforeach
    </div>

    <div class="alert alert-info reveal mt-36" style="max-width:760px;margin-left:auto;margin-right:auto;text-align:center">
      {{ __('site.donate_after') }}
      @if ($site->telegram_url)<br><a href="{{ $site->telegram_url }}" class="link-arrow" style="margin-top:8px" target="_blank" rel="noopener">Telegram {!! \App\Support\Icons::svg('arrow', '', 2) !!}</a>@endif
    </div>

    @if ($page?->t('body'))<div class="prose reveal mt-36">{!! $page->t('body') !!}</div>@endif
  </div>
</section>
</x-layouts.app>
