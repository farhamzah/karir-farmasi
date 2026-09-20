<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Curriculum Vitae — {{ $cv['professional_name'] }}</title>
    <style>
        @page{size:A4;margin:10mm}@page cv07{size:A4;margin:0}*{box-sizing:border-box}html,body{margin:0;color:#1c3028;font-family:Arial,sans-serif;print-color-adjust:exact;-webkit-print-color-adjust:exact}body{overflow-wrap:anywhere}
        .paper{--ink:#1c3028;--muted:#64746b;--accent:#196848;--soft:#e8f1ec;position:relative;width:100%;min-height:277mm;padding:12mm;background:#fff;color:var(--ink);overflow:hidden}.paper:before{content:"";position:absolute;inset:0 0 auto;height:2mm;background:var(--accent)}
        .identity{position:relative;display:flex;gap:7mm;align-items:center;padding-bottom:7mm;border-bottom:.3mm solid #cbd6d0}.photo{width:27mm;height:30mm;flex:none;border:1.2mm solid #fff;border-radius:34% 34% 48% 48%;object-fit:cover;box-shadow:0 2mm 6mm rgba(20,53,40,.16)}.kicker{margin:0 0 2mm;color:var(--accent);font-size:6.5pt;font-weight:700;letter-spacing:.2em;text-transform:uppercase}
        h1{margin:0;color:var(--ink);font:600 27pt/1.02 Georgia,serif;letter-spacing:-.025em;overflow-wrap:normal;word-break:normal}h2{max-width:150mm;margin:2.4mm 0 0;color:#485c52;font-size:10pt;font-weight:400;line-height:1.4}.contact{display:flex;flex-wrap:wrap;gap:2mm 4mm;margin-top:4mm;color:var(--muted);font-size:7pt;font-weight:600}.contact span{display:inline-flex;align-items:center;gap:1.2mm}.contact i{width:4mm;height:4mm;display:inline-grid;place-items:center;border-radius:50%;background:var(--soft);color:var(--accent);font-size:6pt;font-style:normal}a{color:inherit;text-decoration:none}
        .sections{position:relative;display:grid;gap:5.5mm;margin-top:7mm}section{min-width:0;break-inside:auto}.section-heading{display:flex;align-items:center;gap:2.5mm;margin-bottom:2.5mm;border-bottom:.3mm solid #cbd6d0;break-after:avoid}.section-heading span{width:6mm;height:6mm;display:grid;place-items:center;margin-bottom:-.3mm;background:var(--accent);color:#fff;font-size:5.5pt;font-weight:700}h3{margin:0;padding:0 0 2mm;color:var(--ink);font-size:8pt;letter-spacing:.13em;text-transform:uppercase}.items{display:grid;gap:2.4mm}.item{padding:0 0 2.2mm;border-bottom:.25mm solid #edf0ee;break-inside:avoid}.item:last-child{padding-bottom:0;border:0}h4{margin:0 0 1mm;color:var(--ink);font-size:8.5pt;line-height:1.3}.meta{display:flex;flex-wrap:wrap;gap:1mm 3mm}.meta span{color:var(--muted);font-size:7pt;line-height:1.35}.meta small{margin-right:1mm;color:#89958f;font-size:5.3pt;font-weight:700;text-transform:uppercase}.description{margin:1.2mm 0 0;color:#45574e;font-size:7.4pt;line-height:1.45}.links{display:flex;flex-wrap:wrap;gap:1.2mm;margin-top:1.5mm}.links a{padding:1mm 1.8mm;border:.25mm solid #bdd3c7;border-radius:8mm;background:var(--soft);color:var(--accent);font-size:6pt;font-weight:700}
        .summary .item{padding:3.5mm 4mm;border:0;border-left:1mm solid var(--accent);background:var(--soft)}.summary .description{margin:0;font:italic 8pt/1.55 Georgia,serif}.skills .items,.languages .items{display:flex;flex-wrap:wrap;gap:1.3mm}.skills .item,.languages .item{padding:1.5mm 2.3mm;border:.25mm solid #d6e2db;border-radius:8mm;background:#f5f8f6}.skills h4,.languages h4{margin:0;font-size:7pt}.skills .meta,.languages .meta{display:none}
        .cv-01{--ink:#132c25;--accent:#275b48;--soft:#eef3ef;background:linear-gradient(90deg,#f7f5ef 0 5mm,#fff 5mm calc(100% - 5mm),#f7f5ef calc(100% - 5mm))}.cv-01 .identity{border-bottom:.6mm solid var(--ink)}.cv-01 .section-heading span{background:transparent;color:var(--accent);border:.25mm solid #9fb2a8}.cv-01 h3{font-family:Georgia,serif;font-size:8.5pt}
        .cv-02{--ink:#17372c;--accent:#1d704e;--soft:#e4f0e9;padding-top:12mm}.cv-02:before{height:60mm;background:linear-gradient(130deg,#123d30 0%,#1f7452 88%,#d7b86b 88% 90%,#194a38 90%)}.cv-02 .identity{margin:-12mm -12mm 0;padding:10mm 12mm 9mm;border:0;color:#fff}.cv-02 h1{color:#fff}.cv-02 h2,.cv-02 .contact{color:#dce9e2}.cv-02 .kicker{color:#e3c87e}.cv-02 .contact i{background:rgba(255,255,255,.14);color:#fff}.cv-02 .photo{height:32mm;border-radius:15mm 15mm 4mm 4mm}.cv-02 .sections{grid-template-columns:1.15fr .85fr;gap:5mm 7mm}.cv-02 .summary,.cv-02 .experience,.cv-02 .education{grid-column:1/-1}.cv-02 .section-heading span{border-radius:50%}
        .cv-03{--ink:#3c2923;--accent:#ad5b3b;--soft:#f8ebe3;background:linear-gradient(90deg,#f6e7de 0 29%,#fff 29%)}.cv-03:before{left:29%}.cv-03 .photo{border-radius:14mm 14mm 4mm 4mm}.cv-03 h1{font-family:Arial,sans-serif;font-weight:700}.cv-03 .sections{grid-template-columns:.75fr 1.45fr;gap:5mm 8mm}.cv-03 .summary,.cv-03 .experience{grid-column:2}.cv-03 .skills,.cv-03 .languages,.cv-03 .certifications{grid-column:1}.cv-03 .section-heading span{border-radius:1.5mm 1.5mm 0 0}
        .cv-04{--ink:#182c24;--accent:#2f6049;--soft:#e8f1eb;--line:#8ca596;display:grid;grid-template-columns:122mm 68mm;grid-template-rows:auto 1fr;padding:0;background:linear-gradient(90deg,#fff 0 64%,#edf4ef 64%);font-family:Georgia,serif}.cv-04:before{display:none}.cv-04 .identity{position:relative;grid-column:1/-1;display:grid;grid-template-columns:34mm 1fr 44mm;gap:6mm;align-items:start;padding:7mm 8mm 4mm;border:0;background:linear-gradient(105deg,#fff 0 63%,#edf4ef 63%);color:var(--ink)}.cv-04 .identity:after{content:'';position:absolute;right:12mm;top:-12mm;width:52mm;height:38mm;opacity:.34;background:radial-gradient(ellipse at 70% 25%,#bfd1c4 0 26%,transparent 27%),radial-gradient(ellipse at 35% 58%,#c9d9cf 0 28%,transparent 29%);transform:rotate(-18deg)}.cv-04 .photo{position:relative;z-index:1;width:31mm;height:37mm;display:block;margin:0;border:0;border-radius:1.2mm;object-fit:cover;box-shadow:0 3mm 8mm rgba(39,71,57,.16)}.cv-04 .photo-placeholder{display:grid;place-items:center;background:#dce7e1;color:#2f6049;font:700 18pt Georgia,serif}.cv-04 .identity>div{position:relative;z-index:1;padding-top:5mm;min-width:0}.cv-04 .kicker{display:none}.cv-04 h1{color:#111c18;font-size:22pt;line-height:1.04;overflow-wrap:break-word}.cv-04 h2{color:var(--accent);font:650 12pt/1.25 Georgia,serif;overflow-wrap:break-word}.cv-04 h2:after{content:'Apoteker untuk kualitas hidup yang lebih baik.';display:block;margin-top:3mm;color:#2a342f;font:italic 9pt/1.35 Georgia,serif}.cv-04 .contact{position:static;width:auto;z-index:3;display:grid;gap:1.7mm;margin-top:4mm;color:#1d3028;font-size:7pt}.cv-04 .contact i{background:var(--accent);color:#fff}.cv-04 .contact a{color:inherit;text-decoration:none}.cv-04 .sections{grid-column:1/-1;display:grid;grid-template-columns:122mm 68mm;gap:0;align-items:stretch;margin:0}.cv-04 .cv-column{min-width:0;display:block}.cv-04 .cv-main-column{background:#fff}.cv-04 .cv-sidebar-column{min-height:100%;background:linear-gradient(180deg,#edf4ef 0%,#f7faf7 100%)}.cv-04 section{padding:3.2mm 8mm}.cv-04 .skills,.cv-04 .certifications,.cv-04 .events,.cv-04 .event_certificates,.cv-04 .languages,.cv-04 .preferences{padding-left:5mm;padding-right:6mm;background:transparent}.cv-04 .section-heading{gap:3mm;margin-bottom:2mm;border-bottom:.25mm solid var(--line)}.cv-04 .section-heading span{width:8mm;height:8mm;border-radius:50%;background:var(--accent);color:#fff;font-size:0}.cv-04 .section-heading span:before{content:''}.cv-04 h3{padding:0;color:#17241f;font:700 12pt/1.15 Georgia,serif;letter-spacing:0;text-transform:none}.cv-04 .items{display:block}.cv-04 .item{padding:0 0 2mm;border:0}.cv-04 h4{font-size:9.6pt;overflow-wrap:break-word}.cv-04 .meta{gap:.8mm 2.4mm}.cv-04 .meta span,.cv-04 .description{font-size:8pt;overflow-wrap:break-word}.cv-04 .description{line-height:1.34}.cv-04 .skills .items,.cv-04 .certifications .items,.cv-04 .events .items,.cv-04 .event_certificates .items,.cv-04 .languages .items,.cv-04 .preferences .items{gap:1.6mm}.cv-04 .skills .item,.cv-04 .certifications .item,.cv-04 .events .item,.cv-04 .event_certificates .item,.cv-04 .languages .item,.cv-04 .preferences .item{padding:0 0 0 3mm;background:transparent;border:0}.cv-04 .skills .item:before,.cv-04 .certifications .item:before,.cv-04 .events .item:before,.cv-04 .event_certificates .item:before,.cv-04 .languages .item:before,.cv-04 .preferences .item:before{content:'-';float:left;margin-left:-3mm}.cv-04 .links a{border:0;border-radius:1.2mm;background:#d6e4da;color:#17392e;font:500 7.5pt Georgia,serif}        .cv-05{--ink:#203949;--accent:#346b88;--soft:#e8f1f6;font-family:Georgia,serif;background:#fcfdfe}.cv-05:before{height:2.5mm;background:linear-gradient(90deg,#173f5a 0 38%,#63a3c5 38% 73%,#d8b85f 73%)}.cv-05 .identity{border-bottom:.7mm double #7692a1}.cv-05 h1{color:#17394f}.cv-05 h2{color:#476273;font-family:Georgia,serif;font-style:italic}.cv-05 .section-heading span{background:#315e77}.cv-05 h3,.cv-05 h4{font-family:Georgia,serif;color:#254e66}.cv-05 .publications .item{padding-left:3mm;border-left:.5mm solid #acc5d2;border-bottom:0}
        .cv-06{--ink:#17354c;--muted:#536b7d;--accent:#0f7180;--navy:#0c3552;--soft:#f2f6f8;min-height:277mm;padding:3.5mm;background:#fff;color:var(--ink);font-family:Georgia,serif}.cv-06:before{display:none}.impact-header{position:relative;display:grid;grid-template-columns:minmax(0,1fr) 28mm;gap:5mm;min-height:40mm;padding:4mm 4.5mm 3.5mm;border-radius:2mm;background:linear-gradient(105deg,#0b304b,#154761);color:#fff;overflow:hidden}.impact-header:after{content:'';position:absolute;right:29mm;top:0;width:24mm;height:100%;border-left:.25mm solid rgba(255,255,255,.35);background:linear-gradient(135deg,transparent 0 58%,rgba(255,255,255,.05) 58%)}.impact-header-main{position:relative;z-index:1;min-width:0}.impact-brand{display:flex;align-items:center;justify-content:space-between;gap:4mm;margin-bottom:4mm;color:#d9e7ed;font:600 5.3pt/1.2 Arial,sans-serif;letter-spacing:.25em;text-transform:uppercase}.impact-brand:after{content:'';width:28mm;height:.25mm;background:#9db2bf}.impact-header h1{color:#fff;font-size:22pt;line-height:1}.impact-header h2{max-width:none;margin-top:2mm;color:#edf5f7;font:600 7pt/1.25 Arial,sans-serif;letter-spacing:.17em;text-transform:uppercase}.impact-contact{display:flex;flex-wrap:wrap;gap:2mm 5mm;margin-top:3mm;padding-top:2.5mm;border-top:.25mm solid rgba(255,255,255,.55);font:600 6pt/1.25 Arial,sans-serif}.impact-contact span{display:inline-flex;gap:1.2mm;align-items:center}.impact-contact i{font-style:normal;color:#c7d8df}.impact-photo{position:relative;z-index:2;width:28mm;height:34mm;align-self:center;border:1mm solid #fff;object-fit:cover;background:#dbe5ea;box-shadow:0 1.5mm 5mm rgba(0,0,0,.2)}.impact-motto{position:absolute;right:31mm;top:15mm;z-index:1;width:18mm;color:#d8e6ec;font:600 5pt/1.5 Arial,sans-serif;letter-spacing:.16em;text-transform:uppercase}.impact-summary{display:grid;grid-template-columns:1fr 50mm;gap:5mm;margin-top:3.5mm;padding:3mm 4mm;border-radius:2.5mm;background:linear-gradient(120deg,#f2f6f8,#eaf1f5)}.impact-summary h3,.impact-section h3{padding:0;color:#173b57;font:700 9.5pt/1.15 Georgia,serif;letter-spacing:.09em}.impact-summary-heading,.impact-section-heading{display:flex;align-items:center;gap:2.5mm;margin-bottom:2mm;border-bottom:.25mm solid #a9bbc6}.impact-summary-heading span,.impact-section-heading span{width:7mm;height:7mm;display:grid;place-items:center;flex:none;border-radius:50%;background:#0d3b59;color:#fff;font:700 5.3pt/1 Arial,sans-serif}.impact-summary p{margin:0;color:#334e61;font-size:7.4pt;line-height:1.45}.impact-quote{padding-left:4mm;border-left:.35mm solid #9eb1bd;color:#405c70;font:italic 7.2pt/1.5 Georgia,serif}.impact-body{display:grid;grid-template-columns:minmax(0,1.7fr) 60mm;gap:2.5mm;margin-top:2.5mm}.impact-column{display:grid;align-content:start;gap:2.5mm;min-width:0}.impact-section{min-width:0;padding:2.8mm 3mm;border-radius:2.2mm;background:#fff;box-shadow:0 .4mm 2mm rgba(23,53,76,.08);break-inside:avoid}.impact-side .impact-section{background:linear-gradient(140deg,#f5f8fa,#eef3f6)}.impact-items{display:grid;gap:2mm}.impact-item{position:relative;padding:0 0 2mm 7mm;border:0;border-left:.3mm solid #98adba;break-inside:avoid}.impact-item:last-child{padding-bottom:0}.impact-item:before{content:'';position:absolute;left:-1.15mm;top:1mm;width:2mm;height:2mm;border:.5mm solid #fff;border-radius:50%;background:#0d3b59}.impact-item h4{margin:0 0 .7mm;color:#17354c;font-size:7.6pt;line-height:1.25}.impact-item .meta{gap:.7mm 2mm}.impact-item .meta span{color:#566d7c;font-size:6.1pt}.impact-item .description{margin-top:.8mm;color:#405768;font-size:6.5pt;line-height:1.35}.impact-item .links a{padding:.8mm 1.6mm;border:0;border-radius:1mm;background:#dbe8ed;color:#123f58;font-size:5.5pt}.impact-side .skills .impact-items,.impact-side .languages .impact-items,.impact-side .preferences .impact-items{display:flex;flex-wrap:wrap;gap:1mm}.impact-side .skills .impact-item,.impact-side .languages .impact-item,.impact-side .preferences .impact-item{padding:1mm 1.6mm;border:.25mm solid #d7e2e7;border-radius:8mm;background:#fff}.impact-side .skills .impact-item:before,.impact-side .languages .impact-item:before,.impact-side .preferences .impact-item:before{display:none}.impact-side .skills .impact-item h4,.impact-side .languages .impact-item h4,.impact-side .preferences .impact-item h4{margin:0;font-size:6.2pt}.impact-side .skills .impact-item .meta,.impact-side .languages .impact-item .meta,.impact-side .preferences .impact-item .meta{margin-top:.5mm}.impact-links{margin-top:3mm;padding:2.6mm 3mm;border-radius:2mm;background:linear-gradient(120deg,#edf3f6,#e5edf1)}.impact-links h3{margin:0 0 2mm;color:#173b57;font-size:8pt;letter-spacing:.08em;text-transform:uppercase}.impact-link-row{display:flex;flex-wrap:wrap;gap:1.5mm}.impact-link-row a{padding:1.5mm 2.5mm;border-radius:1mm;background:#0d3b59;color:#fff;font:600 6pt/1 Arial,sans-serif}.impact-footer{display:flex;align-items:center;gap:4mm;margin-top:2.5mm;color:#5d7484;font:600 4.8pt/1 Arial,sans-serif;letter-spacing:.18em;text-transform:uppercase}.impact-footer:before,.impact-footer:after{content:'';height:.25mm;flex:1;background:#93a8b5}
        .cv-07{--gold:#b3944f;--cream:#fffdf7;page:cv07;display:grid;grid-template-columns:142mm 68mm;min-height:297mm;padding:0;background:var(--cream);color:#202423;font-family:Georgia,serif}.cv-07:before{inset:0 auto 0 0;width:2mm;height:auto;background:linear-gradient(180deg,#d6bd78,#927434,#d6bd78)}.gold-main{min-width:0;display:flex;flex-direction:column;padding:7mm 6mm 3mm 10mm}.gold-brand{display:flex;align-items:center;gap:4mm;color:#957333;font:700 5pt/1 Arial,sans-serif;letter-spacing:.28em}.gold-brand:after{content:'';height:.25mm;flex:1;background:linear-gradient(90deg,var(--gold),transparent)}.gold-identity{padding-bottom:4mm;border-bottom:.25mm solid #d0b675}.gold-identity h1{margin-top:5mm;color:#121716;font-size:25pt}.gold-identity h2{max-width:none;color:#29302e;font:600 7pt/1.35 Arial,sans-serif;letter-spacing:.18em;text-transform:uppercase}.gold-sections{display:grid;align-content:start;padding-top:2mm}.gold-section{padding:1.8mm 0;break-inside:auto}.gold-heading{display:flex;align-items:center;gap:2.5mm;margin-bottom:1.7mm}.gold-heading:after{content:'';height:.25mm;flex:1;background:linear-gradient(90deg,var(--gold),transparent)}.gold-heading span{width:8mm;height:8mm;display:grid;place-items:center;flex:none;border-radius:50%;background:linear-gradient(145deg,#b79a58,#7f662d);color:#fff;font:700 5pt/1 Arial,sans-serif}.gold-heading h3{padding:0;color:#202423;font:700 11pt/1.1 Georgia,serif;letter-spacing:0;text-transform:none}.gold-items{display:grid;gap:1.5mm}.gold-item{position:relative;padding:0 0 1mm 12mm;border:0;break-inside:avoid}.gold-item:before{content:'';position:absolute;left:9.5mm;top:.3mm;bottom:1mm;width:.25mm;background:#c3a55f}.gold-item h4{margin:0 0 .5mm;color:#202423;font-size:7.5pt}.gold-item .meta{gap:.5mm 2mm}.gold-item .meta span{font-size:5.8pt}.gold-item .meta small{color:#947233;font-size:4.7pt}.gold-item .description{margin-top:.6mm;color:#35403b;font-size:6.5pt;line-height:1.4}.gold-item .links a{border:.25mm solid #cdb574;background:#fff9e9;color:#6f5725;font-size:5pt}.gold-main-footer{margin-top:auto;padding-top:2mm;border-top:.25mm solid #d0b675;color:#92743a;font:italic 6pt/1 Georgia,serif;text-align:right}.gold-side{min-width:0;display:flex;flex-direction:column;padding:7mm 5mm 5mm;background:radial-gradient(circle at 50% 8%,#3b4141,#232828 45%,#1d2222);color:#f8efdc}.gold-photo{width:42mm;height:42mm;display:grid;place-items:center;margin:0 auto 2mm;border:1mm solid #e3c778;border-radius:50%;overflow:hidden;background:#e8e4dc;color:#705825;font:700 20pt Georgia,serif}.gold-photo img{width:100%;height:100%;object-fit:cover;object-position:center top}.gold-signature{display:block;color:#e4c36e;font:italic 22pt/1 "Segoe Script",cursive;text-align:center}.gold-motto{margin:1mm 0 4mm;color:#d9bd77;font:700 4.5pt/1.5 Arial,sans-serif;letter-spacing:.18em;text-align:center}.gold-contact{display:grid;gap:1.5mm;padding-bottom:4mm;border-bottom:.25mm solid #c7aa65;font-size:6.4pt}.gold-contact span{display:flex;align-items:flex-start;gap:2mm}.gold-contact i{width:4mm;flex:none;color:#e7c66f;font-style:normal;text-align:center}.gold-side-sections{display:grid;padding-top:1.5mm}.gold-side-section{padding:2.2mm 0;border-bottom:.25mm solid rgba(218,188,113,.42)}.gold-side-heading{display:flex;align-items:center;gap:2mm;margin-bottom:1.5mm}.gold-side-heading span{width:6mm;color:#e7c66f;font:700 7pt Arial,sans-serif}.gold-side-heading h3,.gold-links h3{padding:0;color:#e9cf8a;font:700 9pt/1.1 Georgia,serif;letter-spacing:0;text-transform:none}.gold-side-items{display:grid;gap:.7mm}.gold-side-item{position:relative;padding-left:3mm;border:0}.gold-side-item:before{content:'•';position:absolute;left:0;color:#e6c56d}.gold-side-item h4{margin:0;color:#fff7e7;font-size:6.4pt;font-weight:500}.gold-side-item .meta{gap:.5mm 1.5mm}.gold-side-item .meta span{color:#d8cfbb;font-size:5.3pt}.gold-side-item .meta small{display:none}.gold-side-item .description{color:#d8d1c1;font-size:5.4pt}.gold-links{padding-top:3mm}.gold-link-row{display:grid;gap:1.3mm;margin-top:2mm}.gold-link-row a{padding:1.6mm 2mm;border:.25mm solid #d5b96f;border-radius:8mm;color:#f3dfaa;font:600 5.4pt Arial,sans-serif;text-align:center}.gold-side-footer{margin-top:auto;padding-top:3mm;border-top:.25mm solid #c7aa65;color:#e2c474;font:700 4.5pt/1.5 Arial,sans-serif;letter-spacing:.17em}
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
    $sectionsByKey = collect($cv['sections'])->keyBy('key');
    $impactLeftKeys = ['education', 'experience', 'publications', 'projects', 'organizations'];
    $impactRightKeys = ['skills', 'certifications', 'event_certificates', 'events', 'languages', 'preferences'];
    $goldMainKeys = ['summary', 'education', 'experience', 'certifications', 'event_certificates', 'events', 'organizations', 'projects', 'publications'];
    $goldSideKeys = ['skills', 'languages', 'preferences'];
    $impactLinks = collect([
        filled($cv['linkedin_url'] ?? null) ? ['href' => $cv['linkedin_url'], 'label' => 'LinkedIn'] : null,
        filled($cv['portfolio_url'] ?? null) ? ['href' => $cv['portfolio_url'], 'label' => 'Lihat Portofolio'] : null,
    ])->filter();
    foreach ($cv['sections'] as $section) {
        foreach ($section['items'] as $item) {
            if (filled($item['credential_url'] ?? null)) {
                $impactLinks->push(['href' => $item['credential_url'], 'label' => $credentialLabel($item)]);
            }
            if (filled($item['project_url'] ?? null)) {
                $impactLinks->push(['href' => $item['project_url'], 'label' => 'Lihat Proyek']);
            }
            if (filled($item['url'] ?? null)) {
                $impactLinks->push([
                    'href' => $item['url'],
                    'label' => str_contains(strtolower($item['url']), 'scholar.google') ? 'Google Scholar' : 'Lihat Publikasi',
                ]);
            }
        }
    }
    $impactLinks = $impactLinks->unique('href')->take(8)->values();
@endphp
@if(($cv['template']['key'] ?? '') === 'cv-06')
<main class="paper cv-06">
    <header class="impact-header">
        <div class="impact-header-main">
            <div class="impact-brand"><span>Pharmacy Alumni</span><span>Science for a healthier tomorrow</span></div>
            <h1>{{ $cv['professional_name'] }}</h1>
            @if($cv['headline'] ?? null)<h2>{{ $cv['headline'] }}</h2>@endif
            @if($contacts)
                <div class="impact-contact">
                    @foreach(array_slice($contacts, 0, 3) as $contact)
                        <span><i>{{ $contact['icon'] }}</i>@if($contact['href'])<a href="{{ $contact['href'] }}">{{ $contact['label'] }}</a>@else{{ $contact['label'] }}@endif</span>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="impact-motto">Better people<br>Healthier lives</div>
        @if(($cv['has_photo'] ?? false) && $photoDataUri)<img class="impact-photo" src="{{ $photoDataUri }}" alt="">@endif
    </header>

    @php
        $summarySection = $sectionsByKey->get('summary');
    @endphp
    @if($summarySection && ! empty($summarySection['items']))
        <section class="impact-summary">
            <div>
                <div class="impact-summary-heading"><span>◆</span><h3>{{ $summarySection['title'] }}</h3></div>
                <p>{{ $summarySection['items'][0]['description'] ?? '' }}</p>
            </div>
            <div class="impact-quote">Ilmu kefarmasian menjadi jembatan kecil menuju masyarakat yang lebih sehat dan bermakna.</div>
        </section>
    @endif

    <div class="impact-body">
        @foreach(['impact-main' => $impactLeftKeys, 'impact-side' => $impactRightKeys] as $columnClass => $sectionKeys)
            <div class="impact-column {{ $columnClass }}">
                @foreach($sectionKeys as $sectionKey)
                    @php
                        $section = $sectionsByKey->get($sectionKey);
                    @endphp
                    @continue(! $section || empty($section['items']))
                    @php
                        $primary = $primaryFields[$section['key']] ?? ['title', 'name'];
                    @endphp
                    <section class="impact-section {{ $section['key'] }}">
                        <div class="impact-section-heading"><span>{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</span><h3>{{ $section['title'] }}</h3></div>
                        <div class="impact-items">
                            @foreach($section['items'] as $item)
                                @php
                                    $title = collect($primary)->map(fn ($field) => $item[$field] ?? null)->filter()->implode(' · ');
                                    $description = $item['description'] ?? null;
                                    $links = collect(['credential_url' => 'Sertifikat', 'project_url' => 'Lihat Proyek', 'url' => 'Lihat Publikasi'])
                                        ->filter(fn ($label, $field) => filled($item[$field] ?? null))
                                        ->map(fn ($label, $field) => ['href' => $item[$field], 'label' => $field === 'credential_url' ? $credentialLabel($item) : ($field === 'url' && str_contains(strtolower($item[$field]), 'scholar.google') ? 'Google Scholar' : $label)]);
                                    $metadata = collect($item)->reject(fn ($value, $field) => in_array($field, array_merge($primary, ['description', 'credential_url', 'project_url', 'url']), true) || $value === null || $value === '' || is_bool($value));
                                @endphp
                                <article class="impact-item">
                                    @if($title)<h4>{{ $title }}</h4>@endif
                                    @if($metadata->isNotEmpty())<div class="meta">@foreach($metadata as $field => $value)<span>@if($fieldLabels[$field] ?? null)<small>{{ $fieldLabels[$field] }}</small>@endif{{ $value }}</span>@endforeach</div>@endif
                                    @if($description)<p class="description">{{ $description }}</p>@endif
                                    @if($links->isNotEmpty())<div class="links">@foreach($links as $link)<a href="{{ $link['href'] }}">{{ $link['label'] }} ↗</a>@endforeach</div>@endif
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endforeach
    </div>

    @if($impactLinks->isNotEmpty())
        <section class="impact-links"><h3>Dokumen &amp; tautan profesional</h3><div class="impact-link-row">@foreach($impactLinks as $link)<a href="{{ $link['href'] }}">{{ $link['label'] }}</a>@endforeach</div></section>
    @endif
    <footer class="impact-footer">Apoteker untuk kehidupan yang lebih baik</footer>
</main>
@elseif(($cv['template']['key'] ?? '') === 'cv-07')
<main class="paper cv-07">
    <div class="gold-main">
        <header class="gold-identity">
            <div class="gold-brand">PHARMACY ALUMNI</div>
            <h1>{{ $cv['professional_name'] }}</h1>
            @if($cv['headline'] ?? null)<h2>{{ $cv['headline'] }}</h2>@endif
        </header>
        <div class="gold-sections">
            @foreach($goldMainKeys as $sectionKey)
                @php $section = $sectionsByKey->get($sectionKey); @endphp
                @continue(! $section || empty($section['items']))
                @php $primary = $primaryFields[$section['key']] ?? ['title', 'name']; @endphp
                <section class="gold-section {{ $section['key'] }}">
                    <div class="gold-heading"><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h3>{{ $section['title'] }}</h3></div>
                    <div class="gold-items">
                        @foreach($section['items'] as $item)
                            @php
                                $title = collect($primary)->map(fn ($field) => $item[$field] ?? null)->filter()->implode(' · ');
                                $description = $item['description'] ?? null;
                                $links = collect(['credential_url' => 'Sertifikat', 'project_url' => 'Lihat Portofolio', 'url' => 'Lihat Publikasi'])
                                    ->filter(fn ($label, $field) => filled($item[$field] ?? null))
                                    ->map(fn ($label, $field) => ['href' => $item[$field], 'label' => $field === 'credential_url' ? $credentialLabel($item) : ($field === 'url' && str_contains(strtolower($item[$field]), 'scholar.google') ? 'Google Scholar' : $label)]);
                                $metadata = collect($item)->reject(fn ($value, $field) => in_array($field, array_merge($primary, ['description', 'credential_url', 'project_url', 'url']), true) || $value === null || $value === '' || is_bool($value));
                            @endphp
                            <article class="gold-item">
                                @if($title)<h4>{{ $title }}</h4>@endif
                                @if($metadata->isNotEmpty())<div class="meta">@foreach($metadata as $field => $value)<span>@if($fieldLabels[$field] ?? null)<small>{{ $fieldLabels[$field] }}</small>@endif{{ $value }}</span>@endforeach</div>@endif
                                @if($description)<p class="description">{{ $description }}</p>@endif
                                @if($links->isNotEmpty())<div class="links">@foreach($links as $link)<a href="{{ $link['href'] }}">{{ $link['label'] }} ↗</a>@endforeach</div>@endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
        <footer class="gold-main-footer">Profesional. Berintegritas. Berdampak.</footer>
    </div>
    <aside class="gold-side">
        <div class="gold-photo">@if(($cv['has_photo'] ?? false) && $photoDataUri)<img src="{{ $photoDataUri }}" alt="">@else{{ $initials }}@endif</div>
        <strong class="gold-signature">{{ str($cv['professional_name'])->before(' ') }}</strong>
        <p class="gold-motto">KESEHATAN LEBIH BAIK<br>UNTUK SEMUA</p>
        @if($contacts)
            <div class="gold-contact">
                @foreach(array_slice($contacts, 0, 3) as $contact)<span><i>{{ $contact['icon'] }}</i>@if($contact['href'])<a href="{{ $contact['href'] }}">{{ $contact['label'] }}</a>@else{{ $contact['label'] }}@endif</span>@endforeach
            </div>
        @endif
        <div class="gold-side-sections">
            @foreach($goldSideKeys as $sectionKey)
                @php $section = $sectionsByKey->get($sectionKey); @endphp
                @continue(! $section || empty($section['items']))
                @php $primary = $primaryFields[$section['key']] ?? ['title', 'name']; @endphp
                <section class="gold-side-section {{ $section['key'] }}">
                    <div class="gold-side-heading"><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h3>{{ $section['title'] }}</h3></div>
                    <div class="gold-side-items">
                        @foreach($section['items'] as $item)
                            @php
                                $title = collect($primary)->map(fn ($field) => $item[$field] ?? null)->filter()->implode(' · ');
                                $description = $item['description'] ?? null;
                                $metadata = collect($item)->reject(fn ($value, $field) => in_array($field, array_merge($primary, ['description', 'credential_url', 'project_url', 'url']), true) || $value === null || $value === '' || is_bool($value));
                            @endphp
                            <article class="gold-side-item">
                                @if($title)<h4>{{ $title }}</h4>@endif
                                @if($metadata->isNotEmpty())<div class="meta">@foreach($metadata as $field => $value)<span>@if($fieldLabels[$field] ?? null)<small>{{ $fieldLabels[$field] }}</small>@endif{{ $value }}</span>@endforeach</div>@endif
                                @if($description)<p class="description">{{ $description }}</p>@endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
        @if($impactLinks->isNotEmpty())
            <section class="gold-links"><h3>Tautan &amp; Dokumen</h3><div class="gold-link-row">@foreach($impactLinks as $link)<a href="{{ $link['href'] }}">{{ $link['label'] }}</a>@endforeach</div></section>
        @endif
        <footer class="gold-side-footer">FARMASI UNTUK KEHIDUPAN YANG LEBIH BAIK</footer>
    </aside>
</main>
@else
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
@endif
</body>
</html>

