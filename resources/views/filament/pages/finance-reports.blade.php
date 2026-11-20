<x-filament-panels::page>
    @php $fmt = fn ($n) => number_format((float) $n, 2); @endphp
    <style>
        .fr-kpis{display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(170px,1fr))}
        .fr-kpi{border-radius:1rem;padding:1rem 1.2rem;background:#fff;border:1px solid rgba(0,0,0,.06);box-shadow:0 1px 2px rgba(0,0,0,.04)}
        .dark .fr-kpi{background:rgba(255,255,255,.04);border-color:rgba(255,255,255,.08)}
        .fr-kpi small{display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#6b7280}
        .fr-kpi b{display:block;font-family:'Cormorant Garamond',serif;font-size:1.9rem;line-height:1.1;color:#0b3d2a}
        .dark .fr-kpi b{color:#fff}
        .fr-kpi.gold{background:linear-gradient(160deg,#0b3d2a,#06301f);color:#fff;border:0}
        .fr-kpi.gold b{color:#c9a45c}.fr-kpi.gold small{color:rgba(255,255,255,.65)}
        .fr-table{width:100%;font-size:.875rem;border-collapse:collapse}
        .fr-table th{text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;padding:.5rem .75rem;border-bottom:1px solid rgba(0,0,0,.08)}
        .fr-table td{padding:.55rem .75rem;border-bottom:1px solid rgba(0,0,0,.05)}
        .dark .fr-table td,.dark .fr-table th{border-color:rgba(255,255,255,.07)}
        .fr-table td.num,.fr-table th.num{text-align:right;font-variant-numeric:tabular-nums}
        .fr-grid{display:grid;gap:1.5rem;grid-template-columns:repeat(auto-fit,minmax(320px,1fr))}
    </style>

    <x-filament::section>
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs font-medium text-gray-500">From</label>
                <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="from" /></x-filament::input.wrapper>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-500">To</label>
                <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="to" /></x-filament::input.wrapper>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach (['this_month' => 'This month', 'last_month' => 'Last month', 'quarter' => 'This quarter', 'year' => 'This year'] as $k => $l)
                    <x-filament::button size="sm" color="gray" outlined wire:click="setRange('{{ $k }}')">{{ $l }}</x-filament::button>
                @endforeach
            </div>
        </div>
    </x-filament::section>

    <div class="fr-kpis">
        <div class="fr-kpi"><small>Fees collected</small><b>{{ $fmt($summary['collected']) }}</b><span class="text-xs text-gray-500">{{ $summary['payments_count'] }} approved payments</span></div>
        <div class="fr-kpi"><small>Donations</small><b>{{ $fmt($summary['donations']) }}</b></div>
        <div class="fr-kpi"><small>Expenses</small><b style="color:#be123c">{{ $fmt($summary['expenses']) }}</b></div>
        <div class="fr-kpi gold"><small>Net (income − expenses)</small><b>{{ $fmt($summary['net']) }}</b><span class="text-xs" style="color:rgba(255,255,255,.65)">ETB</span></div>
        <div class="fr-kpi"><small>Outstanding (all time)</small><b style="color:#b45309">{{ $fmt($summary['outstanding']) }}</b><span class="text-xs text-gray-500">{{ $summary['pending_count'] }} receipts pending review</span></div>
    </div>

    <div class="fr-grid">
        <x-filament::section heading="By program / batch">
            <table class="fr-table">
                <thead><tr><th>Program</th><th>Batch</th><th class="num">#</th><th class="num">ETB</th></tr></thead>
                <tbody>
                @forelse ($byBatch as $row)
                    <tr><td>{{ $row->program }}</td><td>{{ $row->batch }}</td><td class="num">{{ $row->count }}</td><td class="num">{{ $fmt($row->total) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-gray-500">No approved payments in this range.</td></tr>
                @endforelse
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section heading="By payment method">
            <table class="fr-table">
                <thead><tr><th>Method</th><th class="num">#</th><th class="num">ETB</th></tr></thead>
                <tbody>
                @forelse ($byMethod as $row)
                    <tr><td>{{ $row->label }}</td><td class="num">{{ $row->count }}</td><td class="num">{{ $fmt($row->total) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-gray-500">—</td></tr>
                @endforelse
                </tbody>
            </table>
            <h4 class="mt-6 mb-2 text-sm font-semibold">Expenses by category</h4>
            <table class="fr-table">
                <tbody>
                @forelse ($expenses as $row)
                    <tr><td>{{ $row->label }}</td><td class="num">{{ $fmt($row->total) }}</td></tr>
                @empty
                    <tr><td class="text-gray-500">No expenses in this range.</td></tr>
                @endforelse
                </tbody>
            </table>
        </x-filament::section>
    </div>

    <x-filament::section heading="Who still owes" description="All outstanding dues, largest balance first.">
        <div class="overflow-x-auto">
            <table class="fr-table">
                <thead><tr><th>Name</th><th>Phone</th><th>Type</th><th>For</th><th>Oldest due</th><th class="num">Balance (ETB)</th></tr></thead>
                <tbody>
                @forelse ($unpaid as $row)
                    <tr>
                        <td class="font-semibold">{{ $row->name }} @if ($row->overdue)<x-filament::badge color="danger" size="sm" class="inline-flex ml-1">overdue</x-filament::badge>@endif</td>
                        <td>{{ $row->phone }}</td>
                        <td>{{ ucfirst($row->type) }}</td>
                        <td class="text-gray-500">{{ \Illuminate\Support\Str::limit($row->items, 60) }}</td>
                        <td>{{ $row->oldest?->format('d M Y') }}</td>
                        <td class="num font-semibold">{{ $fmt($row->balance) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-gray-500">Nobody owes anything 🎉</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
