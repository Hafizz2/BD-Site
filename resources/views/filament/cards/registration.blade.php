@php
    /** @var \App\Models\Registration $r */
    $r = $getRecord();
    $status = $r->status;
    $initials = collect(preg_split('/\s+/u', trim($r->full_name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $duplicate = $r->existing_student_id && $r->existing_student_id != $r->student_id;
@endphp
<div class="ybd-card">
  <div class="ybd-card-top">
    <span class="ybd-avatar" aria-hidden="true">{{ $initials ?: '?' }}</span>
    <div class="ybd-id">
      <a class="ybd-name" href="{{ \App\Filament\Resources\RegistrationResource::getUrl('view', ['record' => $r]) }}">{{ $r->full_name }}</a>
      <div class="ybd-sub">{{ $r->batch?->label ?? '—' }}</div>
    </div>
    <span class="ybd-status ybd-s-{{ $status?->value }}">{{ $status?->getLabel() }}</span>
  </div>

  <div class="ybd-row">
    <a class="ybd-phone" href="tel:{{ $r->phone }}">
      <x-filament::icon icon="heroicon-m-phone" class="ybd-i" />{{ \App\Support\PhoneNumber::format($r->phone) }}
    </a>
    @if ($r->called_at)
      <span class="ybd-meta" title="{{ $r->call_note }}"><x-filament::icon icon="heroicon-m-check-circle" class="ybd-i ybd-ok" />Called {{ $r->called_at->diffForHumans(short: true) }}@if ($r->call_note) · {{ \Illuminate\Support\Str::limit($r->call_note, 26) }}@endif</span>
    @else
      <span class="ybd-meta ybd-muted"><x-filament::icon icon="heroicon-m-clock" class="ybd-i" />{{ $r->created_at?->diffForHumans(short: true) }} · not called</span>
    @endif
    @if ($duplicate)
      <span class="ybd-chip ybd-chip-warn" title="A student with this phone already exists">Already a student</span>
    @endif
  </div>
</div>
