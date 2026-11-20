@props(['title' => null, 'description' => null, 'image' => null, 'ogType' => 'website', 'transparentNav' => false])
@php
    $siteName = $site->t('site_name');
    $pageTitle = isset($title) && $title ? $title.' — '.$siteName : $siteName.' — '.($site->site_name_am ?? '');
    $metaDescription = $description ?? $site->t('meta_description');
    $ogImage = $image ?? ($site->og_image_path ? asset('storage/'.$site->og_image_path) : asset('images/logo.png'));
    $logo = $site->logo_path ? asset('storage/'.$site->logo_path) : asset('images/pin.png');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $metaDescription), 300) }}">
<link rel="canonical" href="{{ url()->current() }}">
<link rel="alternate" hreflang="en" href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}">
<link rel="alternate" hreflang="am" href="{{ request()->fullUrlWithQuery(['lang' => 'am']) }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $metaDescription), 300) }}">
<meta property="og:type" content="{{ $ogType ?? 'website' }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:locale" content="{{ app()->getLocale() === 'am' ? 'am_ET' : 'en_US' }}">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" type="image/png" href="{{ asset('images/fav64.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/fav180.png') }}">
<meta name="theme-color" content="#0b3d2a">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&family=Noto+Sans+Ethiopic:wght@500;700&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => $site->site_name,
    'alternateName' => $site->site_name_am,
    'url' => url('/'),
    'logo' => asset('images/logo.png'),
    'email' => $site->email,
    'telephone' => $site->phone,
    'address' => $site->address,
    'sameAs' => array_values($site->socials()),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@stack('head')
</head>
<body>

<x-site.nav :logo="$logo" :solid="! ($transparentNav ?? false)" />

<main id="top">
    {{ $slot }}
</main>

<x-site.footer :logo="$logo" />

<button class="to-top" id="toTop" aria-label="{{ __('site.back_to_top') }}">{!! \App\Support\Icons::svg('arrow', '', 2) !!}</button>

<script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
