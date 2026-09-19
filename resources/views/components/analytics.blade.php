@php
    $analytics = app(\GavTaylor\Seo\Analytics::class);
    $seoTrack = $analytics->shouldTrack();
@endphp
@if ($seoTrack)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $analytics->measurementId() }}"></script>
@endif
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
@if ($seoTrack)
        gtag('js', new Date());
        gtag('config', '{{ $analytics->measurementId() }}');
@endif
    </script>
