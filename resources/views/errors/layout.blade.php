{{--
  Error page layout — standalone on purpose: no database, no settings, no Livewire,
  so it still renders when something is broken (500) or the site is in maintenance (503).
--}}
@php
    $am = app()->getLocale() === 'am';
    $isAdmin = request()->is('admin', 'admin/*');
@endphp
<!doctype html>
<html lang="{{ $am ? 'am' : 'en' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>@yield('code') · {{ config('app.name', 'Ye Birhan Dana') }}</title>
  <link rel="icon" href="{{ asset('images/fav64.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600&family=Noto+Sans+Ethiopic:wght@500;700&display=swap" rel="stylesheet">
  <style>
    :root{--g9:#06301f;--g8:#0b3d2a;--g5:#3a8a55;--g1:#e8f2ec;--gold:#c9a45c;--ink:#16231c;--muted:#5d6e64}
    *{box-sizing:border-box;margin:0}
    body{min-height:100vh;min-height:100dvh;display:flex;flex-direction:column;font-family:Inter,'Noto Sans Ethiopic',system-ui,sans-serif;color:var(--ink);
      background:radial-gradient(1200px 600px at 50% -10%,#f4f9f6 0%,#fff 60%)}
    header{padding:18px 20px}
    .brand{display:inline-flex;align-items:center;gap:10px;text-decoration:none}
    .brand img{height:40px;width:auto}
    .brand .am{font-family:'Noto Sans Ethiopic',sans-serif;font-size:.75rem;font-weight:700;color:var(--g5);line-height:1.1}
    .brand .en{font-family:'Cormorant Garamond',Georgia,serif;font-weight:700;font-size:1.15rem;color:var(--g8);letter-spacing:.04em;text-transform:uppercase;line-height:1.1}
    main{flex:1;display:grid;place-items:center;padding:24px 20px 48px}
    .box{max-width:520px;width:100%;text-align:center}
    .code{font-family:'Cormorant Garamond',Georgia,serif;font-weight:700;font-size:clamp(5.5rem,24vw,9rem);line-height:.9;
      background:linear-gradient(135deg,var(--g5),var(--g8));-webkit-background-clip:text;background-clip:text;color:transparent}
    .divider{display:flex;align-items:center;justify-content:center;gap:10px;margin:18px auto}
    .divider i{width:56px;height:1px;background:linear-gradient(90deg,transparent,var(--gold))}
    .divider i:last-child{transform:scaleX(-1)}
    .divider b{width:8px;height:8px;background:var(--gold);transform:rotate(45deg)}
    h1{font-family:'Cormorant Garamond',Georgia,serif;font-size:clamp(1.7rem,6vw,2.2rem);color:var(--g9);line-height:1.15}
    h2{font-family:'Noto Sans Ethiopic',sans-serif;font-size:1.05rem;color:var(--g5);font-weight:700;margin-top:6px}
    p{color:var(--muted);line-height:1.65;margin-top:14px;font-size:.97rem}
    p.am{font-family:'Noto Sans Ethiopic',sans-serif;font-size:.92rem;margin-top:6px}
    .actions{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-top:28px}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:13px 22px;border-radius:999px;font-weight:600;font-size:.94rem;text-decoration:none;cursor:pointer;border:0;font-family:inherit}
    .btn-primary{background:var(--g8);color:#fff;box-shadow:0 10px 24px -12px rgba(6,48,31,.6)}
    .btn-primary:hover{background:var(--g9)}
    .btn-ghost{background:#fff;color:var(--g8);border:1px solid var(--g1)}
    .btn-ghost:hover{border-color:var(--g5)}
    footer{text-align:center;padding:16px;font-size:.78rem;color:var(--muted)}
  </style>
</head>
<body>
  <header>
    <a class="brand" href="{{ url('/') }}">
      <img src="{{ asset('images/logo.png') }}" alt="" width="40" height="40">
      <span><span class="am" style="display:block">የብርሃን ዳና</span><span class="en" style="display:block">Ye Birhan Dana</span></span>
    </a>
  </header>
  <main>
    <div class="box">
      <div class="code">@yield('code')</div>
      <div class="divider"><i></i><b></b><i></i></div>
      <h1>@yield('title')</h1>
      <h2>@yield('title_am')</h2>
      <p>@yield('message')</p>
      <p class="am">@yield('message_am')</p>
      <div class="actions">
        @section('actions')
          @if ($isAdmin)
            <a class="btn btn-primary" href="{{ url('/admin') }}">Back to the dashboard</a>
          @else
            <a class="btn btn-primary" href="{{ url('/') }}">{{ $am ? 'ወደ መነሻ ገጽ' : 'Go to the home page' }}</a>
          @endif
          <a class="btn btn-ghost" href="javascript:history.back()">{{ $am ? 'ወደ ኋላ ተመለስ' : 'Go back' }}</a>
        @show
      </div>
    </div>
  </main>
  <footer>© {{ date('Y') }} {{ config('app.name', 'Ye Birhan Dana') }}</footer>
</body>
</html>
