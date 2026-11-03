/* SmartFood Rescue - front-end behaviour. Plain JavaScript, no build step. */
(function () {
    'use strict';

    const SF = window.SF || { mapDefault: [0, 0, 2] };
    const $  = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

    /* ---------- confirm dialogs: <form data-confirm="Are you sure?"> ---------- */
    document.addEventListener('submit', (e) => {
        const msg = e.target.dataset ? e.target.dataset.confirm : null;
        if (msg && !window.confirm(msg)) e.preventDefault();
    });

    /* ---------- datetime-local inputs can't be in the past ---------- */
    $$('input[type="datetime-local"][data-future]').forEach((el) => {
        const d = new Date(Date.now() - new Date().getTimezoneOffset() * 60000);
        el.min = d.toISOString().slice(0, 16);
    });

    /* ---------- Leaflet ---------- */
    const hasLeaflet = typeof window.L !== 'undefined';
    if (hasLeaflet) {
        const img = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/';
        L.Icon.Default.mergeOptions({
            iconUrl: img + 'marker-icon.png',
            iconRetinaUrl: img + 'marker-icon-2x.png',
            shadowUrl: img + 'marker-shadow.png'
        });
    }

    function baseMap(el, center, zoom) {
        const map = L.map(el).setView(center, zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        el._map = map;
        return map;
    }

    /* Click-to-pin location picker: <div data-map-picker data-lat="#latitude" data-lng="#longitude"> */
    if (hasLeaflet) {
        $$('[data-map-picker]').forEach((el) => {
            const latInput = $(el.dataset.lat);
            const lngInput = $(el.dataset.lng);
            const has = latInput.value && lngInput.value;
            const centre = has ? [parseFloat(latInput.value), parseFloat(lngInput.value)] : SF.mapDefault.slice(0, 2);
            const map = baseMap(el, centre, has ? 15 : SF.mapDefault[2]);
            let marker = has ? L.marker(centre).addTo(map) : null;

            const setPoint = (lat, lng) => {
                latInput.value = lat.toFixed(7);
                lngInput.value = lng.toFixed(7);
                if (marker) marker.setLatLng([lat, lng]); else marker = L.marker([lat, lng]).addTo(map);
            };
            map.on('click', (e) => setPoint(e.latlng.lat, e.latlng.lng));

            const locate = $(el.dataset.locate || '#locate-me');
            if (locate) {
                locate.addEventListener('click', () => {
                    if (!navigator.geolocation) return alert('Location is not supported on this device. Tap the map instead.');
                    navigator.geolocation.getCurrentPosition(
                        (p) => { setPoint(p.coords.latitude, p.coords.longitude); map.setView([p.coords.latitude, p.coords.longitude], 16); },
                        () => alert('Could not read your location. Tap the map to place the pin.')
                    );
                });
            }
            setTimeout(() => map.invalidateSize(), 250);
        });

        /* Read-only map with markers: <div data-map-markers='[{"lat":..,"lng":..,"html":".."}]'> */
        $$('[data-map-markers]').forEach((el) => {
            let items = [];
            try { items = JSON.parse(el.dataset.mapMarkers); } catch (err) { /* ignore bad JSON */ }
            const map = baseMap(el, SF.mapDefault.slice(0, 2), SF.mapDefault[2]);
            const pts = [];
            items.forEach((i) => {
                const m = L.marker([i.lat, i.lng]).addTo(map);
                if (i.html) m.bindPopup(i.html);
                pts.push([i.lat, i.lng]);
            });
            if (el.dataset.mapLine === '1' && pts.length > 1) {
                L.polyline(pts, { color: '#1f7a4d', weight: 3, dashArray: '6 8' }).addTo(map);
            }
            el._refit = () => {
                map.invalidateSize();
                if (pts.length > 1) map.fitBounds(pts, { padding: [30, 30] });
                else if (pts.length === 1) map.setView(pts[0], 14);
            };
            el._refit();
            setTimeout(el._refit, 250);
        });
        // maps inside hidden tabs need a redraw once they become visible
        document.addEventListener('shown.bs.tab', () => $$('[data-map-markers]').forEach((m) => m._refit && m._refit()));
    }

    /* ---------- registration form: show fields for the chosen role ---------- */
    const roleRadios = $$('input[name="role"][data-role-toggle]');
    if (roleRadios.length) {
        const orgTypes = {
            donor: { restaurant: 'Restaurant', bakery: 'Bakery', hotel: 'Hotel', grocery: 'Grocery store' },
            ngo: { ngo: 'NGO', charity: 'Charity' }
        };
        const typeSelect = $('#org_type');
        const apply = () => {
            const role = (roleRadios.find((r) => r.checked) || {}).value;
            $$('[data-role-section]').forEach((sec) => {
                const show = sec.dataset.roleSection.split(' ').includes(role);
                sec.classList.toggle('d-none', !show);
                $$('input, select, textarea', sec).forEach((f) => { f.disabled = !show; });
            });
            if (typeSelect && orgTypes[role]) {
                const keep = typeSelect.dataset.selected || typeSelect.value;
                typeSelect.innerHTML = '<option value="">Select type</option>' +
                    Object.entries(orgTypes[role]).map(([v, l]) => `<option value="${v}"${v === keep ? ' selected' : ''}>${l}</option>`).join('');
            }
            const orgLabel = $('#org-name-label');
            if (orgLabel) orgLabel.textContent = role === 'ngo' ? 'NGO / charity name' : 'Business name';
            $$('[data-map-picker]').forEach((m) => m._map && setTimeout(() => m._map.invalidateSize(), 50));
        };
        roleRadios.forEach((r) => r.addEventListener('change', () => { if (typeSelect) typeSelect.dataset.selected = ''; apply(); }));
        apply();
    }

    /* ---------- QR codes: <div data-qr="token"> ---------- */
    if (typeof window.QRCode !== 'undefined') {
        $$('[data-qr]').forEach((el) => new QRCode(el, { text: el.dataset.qr, width: 200, height: 200, correctLevel: QRCode.CorrectLevel.M }));
    }

    /* ---------- QR scanner on the verify page ---------- */
    const reader = $('#reader');
    if (reader) {
        const startBtn = $('#start-scan');
        const msg = $('#scan-msg');
        let scanner = null;
        startBtn.addEventListener('click', async () => {
            if (typeof window.Html5Qrcode === 'undefined') { msg.textContent = 'Scanner library failed to load. Type the code instead.'; return; }
            startBtn.disabled = true;
            msg.textContent = 'Starting camera...';
            try {
                scanner = new Html5Qrcode('reader');
                await scanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 220, height: 220 } },
                    (text) => {
                        $('#token').value = text.trim();
                        scanner.stop().finally(() => $('#verify-form').submit());
                    }
                );
                msg.textContent = 'Point the camera at the collector\'s QR code.';
            } catch (err) {
                startBtn.disabled = false;
                msg.textContent = 'Camera unavailable (browsers need HTTPS or localhost, plus permission). Type the code instead.';
            }
        });
    }

    /* ---------- dashboard charts ---------- */
    const monthly = $('#chart-monthly');
    if (monthly && typeof window.Chart !== 'undefined') {
        const green = '#1f7a4d', mint = '#7ed3a1', amber = '#f5a524';
        const palette = ['#1f7a4d', '#3fa56f', '#7ed3a1', '#f5a524', '#e4703c', '#4a8fc7', '#8a6bbf'];
        Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
        Chart.defaults.color = '#5d6b63';

        fetch(SF.chartUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then((r) => r.json())
            .then((d) => {
                const noData = (id, has) => { if (!has) $('#' + id).closest('.chart-box').insertAdjacentHTML('beforeend', '<div class="position-absolute top-50 start-50 translate-middle text-body-secondary small">No data yet</div>'); };

                new Chart(monthly, {
                    type: 'bar',
                    data: { labels: d.months, datasets: [{ label: 'Donations posted', data: d.posted, backgroundColor: green, borderRadius: 4 }] },
                    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
                });

                new Chart($('#chart-served'), {
                    type: 'line',
                    data: { labels: d.months, datasets: [{ label: 'People served', data: d.served, borderColor: amber, backgroundColor: 'rgba(245,165,36,.15)', fill: true, tension: .3 }] },
                    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
                });

                new Chart($('#chart-category'), {
                    type: 'doughnut',
                    data: { labels: d.category.labels, datasets: [{ data: d.category.values, backgroundColor: palette, borderWidth: 0 }] },
                    options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } } }
                });
                noData('chart-category', d.category.values.length);

                new Chart($('#chart-status'), {
                    type: 'bar',
                    data: { labels: d.status.labels, datasets: [{ data: d.status.values, backgroundColor: mint, borderRadius: 4 }] },
                    options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
                });
                noData('chart-status', d.status.values.length);
            })
            .catch(() => { /* charts are non-essential */ });
    }
})();
