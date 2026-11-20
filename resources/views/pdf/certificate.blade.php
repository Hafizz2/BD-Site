<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
@if ($ethiopicFont)
@font-face { font-family: 'Ethiopic'; src: url('{{ $ethiopicFont }}') format('truetype'); }
@endif
@page { margin: 0; }
body { margin: 0; font-family: 'DejaVu Serif', serif; color: #06301f; }
.page { position: relative; width: 297mm; height: 210mm; background: #fbf8f1; }
.frame { position: absolute; top: 10mm; left: 10mm; right: 10mm; bottom: 10mm; border: 3px solid #0b3d2a; }
.frame-inner { position: absolute; top: 14mm; left: 14mm; right: 14mm; bottom: 14mm; border: 1px solid #c9a45c; }
.content { position: absolute; top: 26mm; left: 25mm; right: 25mm; text-align: center; }
.logo { height: 26mm; }
.org { font-size: 13pt; letter-spacing: 4px; text-transform: uppercase; color: #3a8a55; margin-top: 3mm; }
.org-am { font-family: 'Ethiopic', 'DejaVu Sans', sans-serif; font-size: 12pt; color: #11553a; }
h1 { font-size: 34pt; margin: 6mm 0 2mm; letter-spacing: 2px; color: #0b3d2a; }
.diamond { display: inline-block; width: 9px; height: 9px; background: #c9a45c; transform: rotate(45deg); margin: 0 6px; }
.lead { font-size: 13pt; color: #5d6e64; margin-top: 6mm; }
.name { font-family: 'Ethiopic', 'DejaVu Serif', serif; font-size: 30pt; font-weight: bold; margin: 4mm 0; color: #06301f; border-bottom: 1px solid #c9a45c; display: inline-block; padding: 0 12mm 2mm; }
.course { font-size: 15pt; margin-top: 3mm; }
.course b { color: #0b3d2a; }
.footer { position: absolute; left: 30mm; right: 30mm; bottom: 26mm; }
.sig { width: 33%; display: inline-block; text-align: center; font-size: 10pt; color: #5d6e64; vertical-align: bottom; }
.sig .line { border-top: 1px solid #0b3d2a; margin: 0 10mm 2mm; }
.no { font-size: 8pt; color: #9aa79f; position: absolute; bottom: 16mm; left: 0; right: 0; text-align: center; }
</style>
</head>
<body>
<div class="page">
  <div class="frame"></div>
  <div class="frame-inner"></div>
  <div class="content">
    <img class="logo" src="{{ public_path('images/logo.png') }}" alt="">
    <div class="org">{{ $site->site_name }}</div>
    <div class="org-am">{{ $site->site_name_am }}</div>
    <h1>Certificate of Completion</h1>
    <div><span class="diamond"></span><span class="diamond"></span><span class="diamond"></span></div>
    <div class="lead">This is to certify that</div>
    <div class="name">{{ $enrollment->student->full_name }}</div>
    <div class="course">has successfully completed <b>{{ $enrollment->batch->program->name }}</b> — {{ $enrollment->batch->name }}</div>
    @if ($enrollment->batch->start_date)
      <div class="lead" style="margin-top:2mm">{{ $enrollment->batch->start_date->format('d M Y') }} – {{ ($enrollment->batch->end_date ?? now())->format('d M Y') }}</div>
    @endif
  </div>
  <div class="footer">
    <div class="sig"><div class="line"></div>Coordinator</div>
    <div class="sig"><div style="font-size:11pt;color:#0b3d2a">{{ $enrollment->certificate_issued_at?->format('d F Y') }}</div><div class="line"></div>Date</div>
    <div class="sig"><div class="line"></div>Lead Teacher</div>
  </div>
  <div class="no">Certificate No. {{ $enrollment->certificate_number }}</div>
</div>
</body>
</html>
