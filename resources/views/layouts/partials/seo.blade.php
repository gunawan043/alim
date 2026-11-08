@php
    $seoName = 'ALIM';
    $seoTagline = 'Academic Learning & Information Management';
    $seoDefaultDescription = 'ALIM (Academic Learning & Information Management) adalah sistem informasi manajemen pendidikan untuk sekolah dan pesantren: data GTK, santri, akademik, keasramaan, dan administrasi dalam satu platform.';

    // @section('title', '...') sudah di-escape oleh Blade, jadi decode sekali
    // di sini agar {{ $seoTitle }} tidak menghasilkan "&amp;amp;".
    $seoPageTitle = trim(htmlspecialchars_decode($__env->yieldContent('title'), ENT_QUOTES));
    $seoTitle = $seoPageTitle !== ''
        ? $seoPageTitle.' — '.$seoName
        : $seoName.' — '.$seoTagline;

    $seoDescription = trim(htmlspecialchars_decode($__env->yieldContent('meta_description'), ENT_QUOTES)) ?: $seoDefaultDescription;
    $seoDescription = trim(preg_replace('/[ \t\r\n]+/', ' ', $seoDescription));
    if (mb_strlen($seoDescription) > 160) {
        $seoDescription = rtrim(mb_substr($seoDescription, 0, 157)).'...';
    }

    $seoCanonical = trim($__env->yieldContent('canonical')) ?: url()->current();

    // Halaman di balik login tidak diindeks mesin pencari.
    $seoRobots = trim($__env->yieldContent('robots'))
        ?: (auth()->check() ? 'noindex, nofollow' : 'index, follow');

    $seoImage = trim($__env->yieldContent('og_image')) ?: asset('build/images/og-alim.png');
    $seoType = trim($__env->yieldContent('og_type')) ?: 'website';
    $seoLocale = 'id_ID';
@endphp

<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<meta name="robots" content="{{ $seoRobots }}">
<meta name="author" content="{{ $seoName }}">
<link rel="canonical" href="{{ $seoCanonical }}">
<meta name="theme-color" content="#405189">

{{-- Open Graph --}}
<meta property="og:type" content="{{ $seoType }}">
<meta property="og:site_name" content="{{ $seoName }}">
<meta property="og:locale" content="{{ $seoLocale }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:image:alt" content="{{ $seoName }} — {{ $seoTagline }}">

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">

{{-- Structured data --}}
<script type="application/ld+json">
{!! json_encode([
    '@'.'context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => $seoName,
            'alternateName' => $seoTagline,
            'url' => url('/'),
            'logo' => asset('build/images/alim-light.png'),
        ],
        [
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'url' => url('/'),
            'name' => $seoName,
            'description' => $seoDefaultDescription,
            'inLanguage' => 'id-ID',
            'publisher' => ['@id' => url('/').'#organization'],
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
