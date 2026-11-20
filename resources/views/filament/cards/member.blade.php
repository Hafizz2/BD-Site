@php
    /** @var \App\Models\Member $m */
    $m = $getRecord();
    $initials = collect(preg_split('/\s+/u', trim($m->full_name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $photo = $m->getFirstMediaUrl('photo', 'thumb');
@endphp
<div class="ybd-card">
  <div class="ybd-card-top">
    @if ($photo)
      <img class="ybd-avatar" src="{{ $photo }}" alt="">
    @else
      <span class="ybd-avatar ybd-avatar-gold" aria-hidden="true">{{ $initials ?: '?' }}</span>
    @endif
    <div class="ybd-id">
      <a class="ybd-name" href="{{ \App\Filament\Resources\MemberResource::getUrl('edit', ['record' => $m]) }}">{{ $m->full_name }}</a>
      <div class="ybd-sub">{{ $m->role_title ?: 'Member' }}</div>
    </div>
    @if ($m->team)<span class="ybd-status ybd-team">{{ $m->team->getLabel() }}</span>@endif
  </div>
  <div class="ybd-row">
    @if ($m->phone)
      <a class="ybd-phone" href="tel:{{ $m->phone }}"><x-filament::icon icon="heroicon-m-phone" class="ybd-i" />{{ \App\Support\PhoneNumber::format($m->phone) }}</a>
    @endif
    @if ($m->contributionPlan)<span class="ybd-chip ybd-chip-gold"><x-filament::icon icon="heroicon-m-banknotes" class="ybd-i" />{{ $m->contributionPlan->name }}</span>@endif
    @if ($m->isTelegramReachable())<span class="ybd-chip ybd-chip-info"><x-filament::icon icon="heroicon-m-paper-airplane" class="ybd-i" />Telegram</span>@endif
    @if (! $m->is_active)<span class="ybd-chip">Inactive</span>@endif
  </div>
</div>
