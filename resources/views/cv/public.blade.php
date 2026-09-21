<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $social['title'] }}</title>
    <meta name="description" content="{{ $social['description'] }}">
    <meta property="og:type" content="profile">
    <meta property="og:site_name" content="SAFA KARIR">
    <meta property="og:title" content="{{ $social['title'] }}">
    <meta property="og:description" content="{{ $social['description'] }}">
    <meta property="og:url" content="{{ $social['url'] }}">
    <meta property="og:image" content="{{ $social['image'] }}">
    <meta property="og:image:secure_url" content="{{ $social['image'] }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Pratinjau CV {{ $social['name'] }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $social['title'] }}">
    <meta name="twitter:description" content="{{ $social['description'] }}">
    <meta name="twitter:image" content="{{ $social['image'] }}">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>
<body>@inertia</body>
</html>
