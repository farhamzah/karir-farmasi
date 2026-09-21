<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        *{box-sizing:border-box}html,body{width:1200px;height:630px;margin:0;overflow:hidden}body{font-family:Arial,sans-serif;color:#15372d;background:#f9f5eb}.card{width:1200px;height:630px;display:grid;grid-template-columns:660px 540px;background:linear-gradient(120deg,#fffdf8,#f4efe3)}.content{padding:52px 58px 48px;display:flex;flex-direction:column}.brand{display:flex;align-items:center;gap:14px;color:#234e3d;font-size:20px;font-weight:800;letter-spacing:.13em}.brand i{width:42px;height:42px;display:grid;place-items:center;border-radius:50%;background:#214b3a;color:#f5df9f;font:700 12px Georgia,serif}.eyebrow{margin:65px 0 14px;color:#a17e3e;font-size:12px;font-weight:800;letter-spacing:.22em;text-transform:uppercase}h1{max-height:178px;margin:0;overflow:hidden;color:#162e24;font:500 60px/1.02 Georgia,serif;letter-spacing:-.045em}h2{max-height:62px;margin:14px 0 0;overflow:hidden;color:#365548;font:italic 24px/1.25 Georgia,serif}.summary{max-height:90px;margin:25px 0 0;overflow:hidden;color:#637168;font-size:17px;line-height:1.5}.footer{margin-top:auto;padding-top:18px;border-top:1px solid #cfbe95;color:#937848;font-size:12px;font-weight:700;letter-spacing:.17em}.visual{position:relative;overflow:hidden;background:radial-gradient(circle at 65% 20%,#e2e8de,#b8c8ba 58%,#739480)}.visual:after{content:"";position:absolute;inset:auto -80px -180px auto;width:580px;height:580px;border:1px solid rgba(255,255,255,.55);border-radius:50%}.visual img{width:100%;height:100%;object-fit:cover;object-position:center 18%}.monogram{position:absolute;inset:0;display:grid;place-items:center;color:#edf2e8;font:500 160px Georgia,serif}.badge{position:absolute;right:35px;bottom:33px;z-index:1;padding:18px 22px;border:1px solid rgba(255,255,255,.7);border-radius:8px;background:rgba(255,253,247,.92);color:#315340;font:700 16px Georgia,serif;box-shadow:0 16px 35px rgba(29,63,45,.18)}
    </style>
</head>
<body>
<div class="card">
    <section class="content"><div class="brand"><i>SK</i> SAFA KARIR</div><p class="eyebrow">Portofolio Alumni Farmasi</p><h1>{{ $cv['professional_name'] }}</h1>@if(filled($cv['headline'] ?? null))<h2>{{ $cv['headline'] }}</h2>@endif
        @php($summary = collect($cv['sections'] ?? [])->firstWhere('key', 'summary')['items'][0]['description'] ?? null)
        @if(filled($summary))<p class="summary">{{ $summary }}</p>@endif
        <div class="footer">PROFIL · PENGALAMAN · KARYA</div>
    </section>
    <section class="visual">@if($photo)<img src="{{ $photo }}" alt="">@else<div class="monogram">{{ collect(explode(' ', trim($cv['professional_name'])))->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') }}</div>@endif<div class="badge">Curriculum Vitae ↗</div></section>
</div>
</body>
</html>
