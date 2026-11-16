@props([
    'nonce' => null,
    'gpcMessage' => 'Analytics is off: your browser sends a Global Privacy Control signal, which this site treats as an opt-out.',
    'offMessage' => 'Analytics is off in this browser.',
    'onMessage' => 'Analytics is on in this browser.',
    'turnedOffMessage' => 'Analytics is off in this browser. Its cookies have been removed.',
    'turnedOnMessage' => 'Analytics is back on in this browser, from the next page you visit.',
    'unsavedMessage' => 'Your browser won\'t let this site save the setting. Blocking cookies in your browser stops analytics too.',
    'turnOffLabel' => 'Turn analytics off',
    'turnOnLabel' => 'Turn analytics back on',
])
@php($analytics = app(\GavTaylor\Seo\Analytics::class))
@php($nonce ??= $analytics->nonce())
@php($id = $attributes->get('id', 'analytics-opt-out'))
<div {{ $attributes->merge(['id' => $id]) }} hidden>
@if ($slot->isEmpty())
    <p data-analytics-status role="status"></p>
    <button type="button" data-analytics-toggle>{{ $turnOffLabel }}</button>
@else
    {{ $slot }}
@endif
</div>
<script{!! $nonce ? ' nonce="'.e($nonce).'"' : '' !!}>
    (function () {
        var control = document.getElementById({!! $analytics->js($id) !!});
        var status = control && control.querySelector('[data-analytics-status]');
        var toggle = control && control.querySelector('[data-analytics-toggle]');
        if (!status || !toggle) { return; }

        var key = {!! $analytics->js($analytics->optOutKey()) !!};
        var measurementId = {!! $analytics->js($analytics->measurementId()) !!};
        var text = {!! $analytics->js([
            'gpc' => $gpcMessage,
            'off' => $offMessage,
            'on' => $onMessage,
            'turnedOff' => $turnedOffMessage,
            'turnedOn' => $turnedOnMessage,
            'unsaved' => $unsavedMessage,
            'turnOff' => $turnOffLabel,
            'turnOn' => $turnOnLabel,
        ]) !!};

        // A browser sending Global Privacy Control has already objected, so it counts as an opt-out.
        function hasGlobalPrivacyControl() {
            return {!! $analytics->honoursGlobalPrivacyControl() ? 'navigator.globalPrivacyControl === true' : 'false' !!};
        }

        function hasOptedOut() {
            if (hasGlobalPrivacyControl()) { return true; }
            try { return window.localStorage.getItem(key) === '1'; } catch (e) { return false; }
        }

        function setOptedOut(optedOut) {
            try {
                if (optedOut) { window.localStorage.setItem(key, '1'); } else { window.localStorage.removeItem(key); }
                return true;
            } catch (e) {
                return false;
            }
        }

        // Google Analytics sets its cookies on the widest domain it can, so clear each level.
        function clearAnalyticsCookies() {
            var names = document.cookie.split(';').map(function (cookie) {
                return cookie.trim().split('=')[0];
            }).filter(function (name) {
                return name === '_ga' || name.indexOf('_ga_') === 0;
            });
            var parts = window.location.hostname.split('.');
            var domains = [''];
            for (var i = 0; i < parts.length - 1; i++) {
                domains.push('; domain=.' + parts.slice(i).join('.'));
            }
            names.forEach(function (name) {
                domains.forEach(function (domain) {
                    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/' + domain;
                });
            });
        }

        function render(message) {
            var optedOut = hasOptedOut();
            status.textContent = message || (hasGlobalPrivacyControl() ? text.gpc : (optedOut ? text.off : text.on));
            toggle.textContent = optedOut ? text.turnOn : text.turnOff;
            toggle.hidden = hasGlobalPrivacyControl();
        }

        toggle.addEventListener('click', function () {
            var optingOut = !hasOptedOut();
            if (!setOptedOut(optingOut)) {
                render(text.unsaved);
                return;
            }
            if (optingOut) {
                // Google's own switch stops the copy already running on this page from sending more.
                if (measurementId) { window['ga-disable-' + measurementId] = true; }
                clearAnalyticsCookies();
                render(text.turnedOff);
            } else {
                render(text.turnedOn);
            }
        });

        control.hidden = false;
        render();
    })();
</script>
