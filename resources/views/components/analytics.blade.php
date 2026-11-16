@props(['nonce' => null])
@php($analytics = app(\GavTaylor\Seo\Analytics::class))
@php($nonce ??= $analytics->nonce())
    <script{!! $nonce ? ' nonce="'.e($nonce).'"' : '' !!}>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
@if ($analytics->shouldTrack())
        (function () {
            var id = {!! $analytics->js($analytics->measurementId()) !!};
            var optedOut = {!! $analytics->honoursGlobalPrivacyControl() ? 'navigator.globalPrivacyControl === true' : 'false' !!};
            try { optedOut = optedOut || window.localStorage.getItem({!! $analytics->js($analytics->optOutKey()) !!}) === '1'; } catch (e) {}
            if (optedOut) { window['ga-disable-' + id] = true; return; }
            gtag('js', new Date());
            gtag('config', id, {!! $analytics->js($analytics->gtagConfig()) !!});
            var script = document.createElement('script');
            script.async = true;
            script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
            document.head.appendChild(script);
        })();
@endif
    </script>
