<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>@yield('title','Receipt')</title>

  <!-- Minimal print styling -->
  <style>
    body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; color:#111; margin:20px; }
    .container { max-width:800px; margin:0 auto; }
    header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    h1 { font-size:20px; margin:0; }
    table { width:100%; border-collapse:collapse; margin-top:10px; }
    th, td { padding:8px 6px; border-bottom:1px solid #eee; text-align:left; }
    .totals { margin-top:16px; display:flex; justify-content:flex-end; gap:24px; }
    .muted { color:#666; font-size:0.92rem; }
    .no-print { margin-top:18px; }
    @media print {
      .no-print { display:none; }
      body { margin:6mm; }
    }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <div>
        <h1>@yield('title','Receipt')</h1>
        <div class="muted">@yield('subtitle')</div>
      </div>
      <div>
        {{-- small logo or organization name --}}
        <strong>SuperTrades Academy</strong>
      </div>
    </header>

    @yield('content')

    <div class="no-print">
      <button onclick="window.print()">Print</button>
    </div>
  </div>
</body>
</html>