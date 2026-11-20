@php
    /** @var \App\Models\Student $s */
    $s = $getRecord();
    $initials = collect(preg_split('/\s+/u', trim($s->full_name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $status = $s->status;
@endphp
<div class="ybd-card">
  <div class="ybd-card-top">
    <span class="ybd-avatar" aria-hidden="true">{{ $initials ?: '?' }}</span>
    <div class="ybd-id">
      <a class="ybd-name" href="{{ \App\Filament\Resources\StudentResource::getUrl('view', ['record' => $s]) }}">{{ $s->full_name }}</a>
      <div class="ybd-sub">{{ $s->latestEnrollment?->batch?->label ?? 'No batch yet' }}</div>
    </div>
    @if ($status)<span class="ybd-status ybd-st-{{ $status->value }}">{{ $status->getLabel() }}</span>@endif
  </div>
  <div class="ybd-row">
    @if ($s->phone)
      <a class="ybd-phone" href="tel:{{ $s->phone }}"><x-filament::icon icon="heroicon-m-phone" class="ybd-i" />{{ \App\Support\PhoneNumber::format($s->phone) }}</a>
    @endif
    @if ($s->isTelegramReachable())<span class="ybd-chip ybd-chip-info"><x-filament::icon icon="heroicon-m-paper-airplane" class="ybd-i" />Telegram</span>@endif
    @if ($s->member)<span class="ybd-chip ybd-chip-gold">Member</span>@endif
  </div>
</div>
