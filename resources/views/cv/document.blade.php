<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Curriculum Vitae — {{ $cv['professional_name'] }}</title>
    <style>
        @page{size:A4;margin:10mm}*{box-sizing:border-box}html,body{margin:0;color:#1c3028;font-family:Arial,sans-serif;print-color-adjust:exact;-webkit-print-color-adjust:exact}body{overflow-wrap:anywhere}
        .paper{--ink:#1c3028;--muted:#64746b;--accent:#196848;--soft:#e8f1ec;position:relative;width:100%;min-height:277mm;padding:12mm;background:#fff;color:var(--ink);overflow:hidden}.paper:before{content:"";position:absolute;inset:0 0 auto;height:2mm;background:var(--accent)}
        .identity{position:relative;display:flex;gap:7mm;align-items:center;padding-bottom:7mm;border-bottom:.3mm solid #cbd6d0}.photo{width:27mm;height:30mm;flex:none;border:1.2mm solid #fff;border-radius:34% 34% 48% 48%;object-fit:cover;box-shadow:0 2mm 6mm rgba(20,53,40,.16)}.kicker{margin:0 0 2mm;color:var(--accent);font-size:6.5pt;font-weight:700;letter-spacing:.2em;text-transform:uppercase}
        h1{margin:0;color:var(--ink);font:600 27pt/1.02 Georgia,serif;letter-spacing:-.025em;overflow-wrap:normal;word-break:normal}h2{max-width:150mm;margin:2.4mm 0 0;color:#485c52;font-size:10pt;font-weight:400;line-height:1.4}.contact{display:flex;flex-wrap:wrap;gap:2mm 4mm;margin-top:4mm;color:var(--muted);font-size:7pt;font-weight:600}.contact span{display:inline-flex;align-items:center;gap:1.2mm}.contact i{width:4mm;height:4mm;display:inline-grid;place-items:center;border-radius:50%;background:var(--soft);color:var(--accent);font-size:6pt;font-style:normal}a{color:inherit;text-decoration:none}
        .sections{position:relative;display:grid;gap:5.5mm;margin-top:7mm}section{min-width:0;break-inside:auto}.section-heading{display:flex;align-items:center;gap:2.5mm;margin-bottom:2.5mm;border-bottom:.3mm solid #cbd6d0;break-after:avoid}.section-heading span{width:6mm;height:6mm;display:grid;place-items:center;margin-bottom:-.3mm;background:var(--accent);color:#fff;font-size:5.5pt;font-weight:700}h3{margin:0;padding:0 0 2mm;color:var(--ink);font-size:8pt;letter-spacing:.13em;text-transform:uppercase}.items{display:grid;gap:2.4mm}.item{padding:0 0 2.2mm;border-bottom:.25mm solid #edf0ee;break-inside:avoid}.item:last-child{padding-bottom:0;border:0}h4{margin:0 0 1mm;color:var(--ink);font-size:8.5pt;line-height:1.3}.meta{display:flex;flex-wrap:wrap;gap:1mm 3mm}.meta span{color:var(--muted);font-size:7pt;line-height:1.35}.meta small{margin-right:1mm;color:#89958f;font-size:5.3pt;font-weight:700;text-transform:uppercase}.description{margin:1.2mm 0 0;color:#45574e;font-size:7.4pt;line-height:1.45}.links{display:flex;flex-wrap:wrap;gap:1.2mm;margin-top:1.5mm}.links a{padding:1mm 1.8mm;border:.25mm solid #bdd3c7;border-radius:8mm;background:var(--soft);color:var(--accent);font-size:6pt;font-weight:700}
        .summary .item{padding:3.5mm 4mm;border:0;border-left:1mm solid var(--accent);background:var(--soft)}.summary .description{margin:0;font:italic 8pt/1.55 Georgia,serif}.skills .items,.languages .items{display:flex;flex-wrap:wrap;gap:1.3mm}.skills .item,.languages .item{padding:1.5mm 2.3mm;border:.25mm solid #d6e2db;border-radius:8mm;background:#f5f8f6}.skills h4,.languages h4{margin:0;font-size:7pt}.skills .meta,.languages .meta{display:none}
        .cv-01{--ink:#132c25;--accent:#275b48;--soft:#eef3ef;background:linear-gradient(90deg,#f7f5ef 0 5mm,#fff 5mm calc(100% - 5mm),#f7f5ef calc(100% - 5mm))}.cv-01 .identity{border-bottom:.6mm solid var(--ink)}.cv-01 .section-heading span{background:transparent;color:var(--accent);border:.25mm solid #9fb2a8}.cv-01 h3{font-family:Georgia,serif;font-size:8.5pt}
        .cv-02{--ink:#17372c;--accent:#1d704e;--soft:#e4f0e9;padding-top:12mm}.cv-02:before{height:60mm;background:linear-gradient(130deg,#123d30 0%,#1f7452 88%,#d7b86b 88% 90%,#194a38 90%)}.cv-02 .identity{margin:-12mm -12mm 0;padding:10mm 12mm 9mm;border:0;color:#fff}.cv-02 h1{color:#fff}.cv-02 h2,.cv-02 .contact{color:#dce9e2}.cv-02 .kicker{color:#e3c87e}.cv-02 .contact i{background:rgba(255,255,255,.14);color:#fff}.cv-02 .photo{height:32mm;border-radius:15mm 15mm 4mm 4mm}.cv-02 .sections{grid-template-columns:1.15fr .85fr;gap:5mm 7mm}.cv-02 .summary,.cv-02 .experience,.cv-02 .education{grid-column:1/-1}.cv-02 .section-heading span{border-radius:50%}
        .cv-03{--ink:#3c2923;--accent:#ad5b3b;--soft:#f8ebe3;background:linear-gradient(90deg,#f6e7de 0 29%,#fff 29%)}.cv-03:before{left:29%}.cv-03 .photo{border-radius:14mm 14mm 4mm 4mm}.cv-03 h1{font-family:Arial,sans-serif;font-weight:700}.cv-03 .sections{grid-template-columns:.75fr 1.45fr;gap:5mm 8mm}.cv-03 .summary,.cv-03 .experience{grid-column:2}.cv-03 .skills,.cv-03 .languages,.cv-03 .certifications{grid-column:1}.cv-03 .section-heading span{border-radius:1.5mm 1.5mm 0 0}
        .cv-04{--ink:#182c24;--accent:#2f6049;--soft:#e8f1eb;--line:#8ca596;display:grid;grid-template-columns:122mm 68mm;grid-template-rows:auto 1fr;padding:0;background:linear-gradient(90deg,#fff 0 64%,#edf4ef 64%);font-family:Georgia,serif}.cv-04:before{display:none}.cv-04 .identity{position:relative;grid-column:1/-1;display:grid;grid-template-columns:34mm 1fr 44mm;gap:6mm;align-items:start;padding:7mm 8mm 4mm;border:0;background:linear-gradient(105deg,#fff 0 63%,#edf4ef 63%);color:var(--ink)}.cv-04 .identity:after{content:'';position:absolute;right:12mm;top:-12mm;width:52mm;height:38mm;opacity:.34;background:radial-gradient(ellipse at 70% 25%,#bfd1c4 0 26%,transparent 27%),radial-gradient(ellipse at 35% 58%,#c9d9cf 0 28%,transparent 29%);transform:rotate(-18deg)}.cv-04 .photo{position:relative;z-index:1;width:31mm;height:37mm;display:block;margin:0;border:0;border-radius:1.2mm;object-fit:cover;box-shadow:0 3mm 8mm rgba(39,71,57,.16)}.cv-04 .photo-placeholder{display:grid;place-items:center;background:#dce7e1;color:#2f6049;font:700 18pt Georgia,serif}.cv-04 .identity>div{position:relative;z-index:1;padding-top:5mm;min-width:0}.cv-04 .kicker{display:none}.cv-04 h1{color:#111c18;font-size:22pt;line-height:1.04;overflow-wrap:break-word}.cv-04 h2{color:var(--accent);font:650 12pt/1.25 Georgia,serif;overflow-wrap:break-word}.cv-04 h2:after{content:'Apoteker untuk kualitas hidup yang lebih baik.';display:block;margin-top:3mm;color:#2a342f;font:italic 9pt/1.35 Georgia,serif}.cv-04 .contact{position:static;width:auto;z-index:3;display:grid;gap:1.7mm;margin-top:4mm;color:#1d3028;font-size:7pt}.cv-04 .contact i{background:var(--accent);color:#fff}.cv-04 .contact a{color:inherit;text-decoration:none}.cv-04 .sections{grid-column:1/-1;display:grid;grid-template-columns:122mm 68mm;gap:0;align-items:stretch;margin:0}.cv-04 .cv-column{min-width:0;display:block}.cv-04 .cv-main-column{background:#fff}.cv-04 .cv-sidebar-column{min-height:100%;background:linear-gradient(180deg,#edf4ef 0%,#f7faf7 100%)}.cv-04 section{padding:3.2mm 8mm}.cv-04 .skills,.cv-04 .certifications,.cv-04 .events,.cv-04 .event_certificates,.cv-04 .languages,.cv-04 .preferences{padding-left:5mm;padding-right:6mm;background:transparent}.cv-04 .section-heading{gap:3mm;margin-bottom:2mm;border-bottom:.25mm solid var(--line)}.cv-04 .section-heading span{width:8mm;height:8mm;border-radius:50%;background:var(--accent);color:#fff;font-size:0}.cv-04 .section-heading span:before{content:''}.cv-04 h3{padding:0;color:#17241f;font:700 12pt/1.15 Georgia,serif;letter-spacing:0;text-transform:none}.cv-04 .items{display:block}.cv-04 .item{padding:0 0 2mm;border:0}.cv-04 h4{font-size:9.6pt;overflow-wrap:break-word}.cv-04 .meta{gap:.8mm 2.4mm}.cv-04 .meta span,.cv-04 .description{font-size:8pt;overflow-wrap:break-word}.cv-04 .description{line-height:1.34}.cv-04 .skills .items,.cv-04 .certifications .items,.cv-04 .events .items,.cv-04 .event_certificates .items,.cv-04 .languages .items,.cv-04 .preferences .items{gap:1.6mm}.cv-04 .skills .item,.cv-04 .certifications .item,.cv-04 .events .item,.cv-04 .event_certificates .item,.cv-04 .languages .item,.cv-04 .preferences .item{padding:0 0 0 3mm;background:transparent;border:0}.cv-04 .skills .item:before,.cv-04 .certifications .item:before,.cv-04 .events .item:before,.cv-04 .event_certificates .item:before,.cv-04 .languages .item:before,.cv-04 .preferences .item:before{content:'-';float:left;margin-left:-3mm}.cv-04 .links a{border:0;border-radius:1.2mm;background:#d6e4da;color:#17392e;font:500 7.5pt Georgia,serif}        .cv-05{--ink:#203949;--accent:#346b88;--soft:#e8f1f6;font-family:Georgia,serif;background:#fcfdfe}.cv-05:before{height:2.5mm;background:linear-gradient(90deg,#173f5a 0 38%,#63a3c5 38% 73%,#d8b85f 73%)}.cv-05 .identity{border-bottom:.7mm double #7692a1}.cv-05 h1{color:#17394f}.cv-05 h2{color:#476273;font-family:Georgia,serif;font-style:italic}.cv-05 .section-heading span{background:#315e77}.cv-05 h3,.cv-05 h4{font-family:Georgia,serif;color:#254e66}.cv-05 .publications .item{padding-left:3mm;border-left:.5mm solid #acc5d2;border-bottom:0}
    </style>
</head>
<body>
@php
    $primaryFields = ['summary'=>[],'education'=>['program_name','degree'],'experience'=>['title'],'skills'=>['name'],'certifications'=>['title'],'organizations'=>['role'],'projects'=>['title'],'publications'=>['title'],'languages'=>['language'],'preferences'=>['target_roles'],'events'=>['title'],'event_certificates'=>['title']];
    $sidebarSectionKeys = ['skills', 'certifications', 'events', 'event_certificates', 'languages', 'preferences'];
    $fieldLabels = ['institution_name'=>'Institusi','program_name'=>'Program studi','degree'=>'Gelar','organization'=>'Organisasi','type'=>'Jenis','location'=>'Lokasi','category'=>'Fokus','level'=>'Tingkat','start_date'=>'Mulai','end_date'=>'Selesai','start_year'=>'Mulai','end_year'=>'Selesai','status'=>'Status','issuer'=>'Penerbit','issue_date'=>'Terbit','expiry_date'=>'Berlaku hingga','credential_id'=>'ID kredensial','role'=>'Peran','proficiency'=>'Kemahiran','publication_name'=>'Media','published_on'=>'Terbit','doi'=>'DOI','event_type'=>'Jenis','organizer'=>'Penyelenggara','date'=>'Tanggal','topics'=>'Topik','certificate_number'=>'Nomor sertifikat','target_roles'=>'Bidang / posisi diminati','employment_types'=>'Jenis pekerjaan','preferred_locations'=>'Lokasi harapan','willing_to_relocate'=>'Relokasi','availability_date'=>'Siap mulai'];
    $credentialLabel = function (array $item): string {
        $title = strtolower((string) ($item['title'] ?? ''));
        if (str_contains($title, 'cpob')) return 'Lihat Sertifikat CPOB';
        if (str_contains($title, 'halal')) return 'Lihat Sertifikat Halal';
        if (str_contains($title, 'kompetensi')) return 'Lihat Sertifikat Kompetensi';
        if (str_contains($title, 'iso')) return 'Lihat Sertifikat ISO';
        if (str_contains($title, 'ijazah')) return 'Lihat Ijazah';
        if (str_contains($title, 'transkrip')) return 'Lihat Transkrip';

        return 'Lihat Sertifikat';
    };
    $contacts = array_values(array_filter([
        ($cv['city']??null)?['icon'=>'⌖','label'=>$cv['city'],'href'=>null]:null,
        ($cv['email']??null)?['icon'=>'@','label'=>$cv['email'],'href'=>'mailto:'.$cv['email']]:null,
        ($cv['whatsapp']??null)?['icon'=>'◉','label'=>$cv['whatsapp'],'href'=>'https://wa.me/'.preg_replace('/\D/','',$cv['whatsapp'])]:null,
        ($cv['linkedin_url']??null)?['icon'=>'↗','label'=>'LinkedIn','href'=>$cv['linkedin_url']]:null,
        ($cv['portfolio_url']??null)?['icon'=>'↗','label'=>'Portofolio','href'=>$cv['portfolio_url']]:null,
    ]));
    $initials = collect(preg_split('/\s+/', trim((string) $cv['professional_name'])))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('');
@endphp
<main class="paper {{ $cv['template']['key'] }}">
    <header class="identity">
        @if(($cv['has_photo']??false)&&$photoDataUri)<img class="photo" src="{{ $photoDataUri }}" alt="">@elseif(($cv['template']['key'] ?? '') === 'cv-04')<div class="photo photo-placeholder">{{ $initials }}</div>@endif
        <div><p class="kicker">Curriculum Vitae</p><h1>{{ $cv['professional_name'] }}</h1>@if($cv['headline']??null)<h2>{{ $cv['headline'] }}</h2>@endif
            @if($contacts)<div class="contact">@foreach($contacts as $contact)<span><i>{{ $contact['icon'] }}</i>@if($contact['href'])<a href="{{ $contact['href'] }}">{{ $contact['label'] }}</a>@else{{ $contact['label'] }}@endif</span>@endforeach</div>@endif
        </div>
    </header>
    <div class="sections">
        @if(($cv['template']['key'] ?? '') === 'cv-04')
            @foreach(['cv-main-column' => array_filter($cv['sections'], fn ($section) => ! in_array($section['key'], $sidebarSectionKeys, true)), 'cv-sidebar-column' => array_filter($cv['sections'], fn ($section) => in_array($section['key'], $sidebarSectionKeys, true))] as $columnClass => $sections)
                <div class="cv-column {{ $columnClass }}">
                    @foreach($sections as $sectionIndex=>$section)
                        @php
                            $primary=$primaryFields[$section['key']]??['title','name'];
                        @endphp
                        <section class="{{ $section['key'] }}"><div class="section-heading"><span>{{ str_pad((string)($sectionIndex+1),2,'0',STR_PAD_LEFT) }}</span><h3>{{ $section['title'] }}</h3></div><div class="items">
                            @foreach($section['items'] as $item)
                                @php
                                    $title=collect($primary)->map(fn($field)=>$item[$field]??null)->filter()->implode(' · ');
                                    $description=$item['description']??null;
                                    $links=collect(['credential_url'=>'Sertifikat','project_url'=>'Lihat Portofolio','url'=>'Publikasi'])->filter(fn($label,$field)=>filled($item[$field]??null))->map(fn($label,$field)=>['href'=>$item[$field],'label'=>$field==='credential_url'?$credentialLabel($item):($field==='url'&&str_contains(strtolower($item[$field]),'scholar.google')?'Google Scholar':$label)]);
                                    $metadata=collect($item)->reject(fn($value,$field)=>in_array($field,array_merge($primary,['description','credential_url','project_url','url']),true)||$value===null||$value===''||is_bool($value));
                                @endphp
                                <article class="item">@if($title)<h4>{{ $title }}</h4>@endif
                                    @if($metadata->isNotEmpty())<div class="meta">@foreach($metadata as $field=>$value)<span>@if($fieldLabels[$field]??null)<small>{{ $fieldLabels[$field] }}</small>@endif{{ $value }}</span>@endforeach</div>@endif
                                    @if($description)<p class="description">{{ $description }}</p>@endif
                                    @if($links->isNotEmpty())<div class="links">@foreach($links as $link)<a href="{{ $link['href'] }}">{{ $link['label'] }} ↗</a>@endforeach</div>@endif
                                </article>
                            @endforeach
                        </div></section>
                    @endforeach
                </div>
            @endforeach
        @else
        @foreach($cv['sections'] as $sectionIndex=>$section)
            @php
                $primary=$primaryFields[$section['key']]??['title','name'];
            @endphp
            <section class="{{ $section['key'] }}"><div class="section-heading"><span>{{ str_pad((string)($sectionIndex+1),2,'0',STR_PAD_LEFT) }}</span><h3>{{ $section['title'] }}</h3></div><div class="items">
                @foreach($section['items'] as $item)
                    @php
                        $title=collect($primary)->map(fn($field)=>$item[$field]??null)->filter()->implode(' · ');
                        $description=$item['description']??null;
                        $links=collect(['credential_url'=>'Sertifikat','project_url'=>'Lihat Portofolio','url'=>'Publikasi'])->filter(fn($label,$field)=>filled($item[$field]??null))->map(fn($label,$field)=>['href'=>$item[$field],'label'=>$field==='credential_url'?$credentialLabel($item):($field==='url'&&str_contains(strtolower($item[$field]),'scholar.google')?'Google Scholar':$label)]);
                        $metadata=collect($item)->reject(fn($value,$field)=>in_array($field,array_merge($primary,['description','credential_url','project_url','url']),true)||$value===null||$value===''||is_bool($value));
                    @endphp
                    <article class="item">@if($title)<h4>{{ $title }}</h4>@endif
                        @if($metadata->isNotEmpty())<div class="meta">@foreach($metadata as $field=>$value)<span>@if($fieldLabels[$field]??null)<small>{{ $fieldLabels[$field] }}</small>@endif{{ $value }}</span>@endforeach</div>@endif
                        @if($description)<p class="description">{{ $description }}</p>@endif
                        @if($links->isNotEmpty())<div class="links">@foreach($links as $link)<a href="{{ $link['href'] }}">{{ $link['label'] }} ↗</a>@endforeach</div>@endif
                    </article>
                @endforeach
            </div></section>
        @endforeach
        @endif
    </div>
</main>
</body>
</html>

