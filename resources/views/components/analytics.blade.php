@if (app(\GavTaylor\Seo\Analytics::class)->shouldTrack())
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ app(\GavTaylor\Seo\Analytics::class)->measurementId() }}"></script>
@endif
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
@if (app(\GavTaylor\Seo\Analytics::class)->shouldTrack())
        gtag('js', new Date());
        gtag('config', '{{ app(\GavTaylor\Seo\Analytics::class)->measurementId() }}');
@endif
    </script>
