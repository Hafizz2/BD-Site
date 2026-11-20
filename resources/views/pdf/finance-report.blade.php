<!DOCTYPE html>
<html><head><meta charset="utf-8">
<style>
body{font-family:'DejaVu Sans',sans-serif;font-size:10pt;color:#16231c}
h1{font-size:18pt;color:#0b3d2a;margin:0}
.sub{color:#5d6e64;margin-bottom:14px}
.kpis td{padding:8px 12px;border:1px solid #e8f2ec}
.kpis b{font-size:13pt;color:#0b3d2a}
table{width:100%;border-collapse:collapse;margin-top:6px}
th{background:#0b3d2a;color:#fff;text-align:left;padding:5px 7px;font-size:8.5pt}
td{padding:5px 7px;border-bottom:1px solid #e8f2ec}
.num{text-align:right}
h2{font-size:12pt;color:#0b3d2a;margin:18px 0 0;border-bottom:2px solid #c9a45c;padding-bottom:3px}
.head{display:table;width:100%}
.head img{height:48px}
</style></head>
<body>
@php $s = $r->summary(); $f = fn ($n) => number_format((float) $n, 2); @endphp
<table style="margin:0"><tr>
  <td style="border:0;width:60px"><img src="{{ public_path('images/logo.png') }}" style="height:52px"></td>
  <td style="border:0"><h1>{{ $site->site_name }} — Finance report</h1><div class="sub">{{ $r->from->format('d M Y') }} – {{ $r->to->format('d M Y') }} · generated {{ now()->format('d M Y H:i') }}</div></td>
</tr></table>

<table class="kpis"><tr>
  <td>Fees collected<br><b>{{ $f($s['collected']) }}</b></td>
  <td>Donations<br><b>{{ $f($s['donations']) }}</b></td>
  <td>Expenses<br><b>{{ $f($s['expenses']) }}</b></td>
  <td>Net<br><b>{{ $f($s['net']) }}</b></td>
  <td>Outstanding<br><b>{{ $f($s['outstanding']) }}</b></td>
</tr></table>

<h2>By program / batch</h2>
<table><tr><th>Program</th><th>Batch</th><th class="num">Payments</th><th class="num">ETB</th></tr>
@foreach ($r->byBatch() as $x)<tr><td>{{ $x->program }}</td><td>{{ $x->batch }}</td><td class="num">{{ $x->count }}</td><td class="num">{{ $f($x->total) }}</td></tr>@endforeach
</table>

<h2>By method</h2>
<table><tr><th>Method</th><th class="num">Payments</th><th class="num">ETB</th></tr>
@foreach ($r->byMethod() as $x)<tr><td>{{ $x->label }}</td><td class="num">{{ $x->count }}</td><td class="num">{{ $f($x->total) }}</td></tr>@endforeach
</table>

<h2>Expenses by category</h2>
<table><tr><th>Category</th><th class="num">ETB</th></tr>
@foreach ($r->expensesByCategory() as $x)<tr><td>{{ $x->label }}</td><td class="num">{{ $f($x->total) }}</td></tr>@endforeach
</table>

<h2>Unpaid</h2>
<table><tr><th>Name</th><th>Phone</th><th>For</th><th>Oldest due</th><th class="num">Balance</th></tr>
@foreach ($r->unpaid() as $x)<tr><td>{{ $x->name }}{{ $x->overdue ? ' (overdue)' : '' }}</td><td>{{ $x->phone }}</td><td>{{ \Illuminate\Support\Str::limit($x->items, 50) }}</td><td>{{ $x->oldest?->format('d M Y') }}</td><td class="num">{{ $f($x->balance) }}</td></tr>@endforeach
</table>
</body></html>
