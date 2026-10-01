(() => {
    'use strict';

    const root = document.documentElement;
    const charts = [];
    const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

    function refreshTheme() {
        const dark = root.dataset.theme === 'dark';
        document.querySelectorAll('.theme-toggle').forEach(button => {
            button.textContent = dark ? '☾ Night' : '☀ Light';
            button.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
        });

        const text = getComputedStyle(root).getPropertyValue('--text-muted').trim();
        const border = getComputedStyle(root).getPropertyValue('--border').trim();
        charts.forEach(chart => {
            chart.options.color = text;
            if (chart.options.scales?.x) {
                chart.options.scales.x.ticks.color = text;
                chart.options.scales.y.ticks.color = text;
                chart.options.scales.x.grid.color = border;
                chart.options.scales.y.grid.color = border;
            }
            chart.update('none');
        });
    }

    document.querySelectorAll('.theme-toggle').forEach(button => {
        button.addEventListener('click', () => {
            root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
            try {
                localStorage.setItem('hostelwash.theme', root.dataset.theme);
            } catch (_) {
                // Theme still works for the current page when storage is blocked.
            }
            refreshTheme();
        });
    });

    const menuButton = document.querySelector('.menu-toggle');
    function closeSidebar() {
        document.body.classList.remove('sidebar-open');
        menuButton?.setAttribute('aria-expanded', 'false');
    }
    menuButton?.addEventListener('click', () => {
        const open = document.body.classList.toggle('sidebar-open');
        menuButton.setAttribute('aria-expanded', String(open));
    });
    document.querySelector('.sidebar-shade')?.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });
    document.querySelector('.public-menu-toggle')?.addEventListener('click', event => {
        const open = document.querySelector('.public-nav').classList.toggle('open');
        event.currentTarget.setAttribute('aria-expanded', String(open));
    });

    document.querySelectorAll('.dismiss-notice').forEach(button => {
        button.addEventListener('click', () => button.closest('.notice').remove());
    });
    document.querySelectorAll('.go-back').forEach(button => {
        button.addEventListener('click', () => history.back());
    });
    document.querySelectorAll('.print-page').forEach(button => {
        button.addEventListener('click', () => window.print());
    });
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
    document.querySelectorAll('form[method="post"]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!event.defaultPrevented && form.checkValidity()) {
                const button = event.submitter;
                if (button) {
                    button.disabled = true;
                    button.classList.add('submitting');
                    button.setAttribute('aria-busy', 'true');
                }
            }
        });
    });
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('.submitting').forEach(button => {
            button.disabled = false;
            button.classList.remove('submitting');
            button.removeAttribute('aria-busy');
        });
    });

    const booking = document.querySelector('#booking-form');
    if (booking) {
        const hostel = document.querySelector('#hostel-select');
        const rooms = document.querySelector('#room-select');
        const roomOptions = [...rooms.options].slice(1).map(option => option.cloneNode(true));
        function updateRooms() {
            rooms.replaceChildren(new Option('Select room', ''));
            roomOptions.filter(option => option.dataset.hostel === hostel.value)
                .forEach(option => rooms.append(option.cloneNode(true)));
        }
        hostel.addEventListener('change', updateRooms);
        updateRooms();

        function estimate() {
            const service = booking.querySelector('input[name="service_id"]:checked');
            const quantity = booking.querySelector('#quantity');
            if (!service) {
                return;
            }
            const perItem = service.dataset.unit === 'per_item';
            quantity.step = perItem ? '1' : '0.1';
            quantity.min = perItem ? '1' : '0.1';
            document.querySelector('#quantity-label').textContent = perItem ? 'Estimated item count' : 'Estimated weight (kg)';
            const value = Number(quantity.value) * Number(service.dataset.price);
            document.querySelector('#estimated-price').textContent = `Rs. ${Number.isFinite(value) ? value.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '—'}`;
            document.querySelector('#price-equation').textContent = `${quantity.value || 0} ${perItem ? 'items' : 'kg'} × Rs. ${service.dataset.price}`;
        }
        booking.addEventListener('input', estimate);
        estimate();
    }

    if (window.Chart) {
        document.querySelectorAll('[data-chart]').forEach(canvas => {
            const points = JSON.parse(canvas.dataset.points);
            const type = canvas.dataset.chart;
            const circular = type === 'doughnut';
            const options = {
                responsive: true,
                maintainAspectRatio: false,
                animation: reducedMotion ? false : {duration: 300},
                plugins: {legend: {display: circular, position: 'bottom'}},
            };
            if (!circular) {
                options.scales = {
                    x: {grid: {display: false}, ticks: {}},
                    y: {beginAtZero: true, ticks: {precision: 0}, grid: {}},
                };
            }
            charts.push(new Chart(canvas, {
                type,
                data: {
                    labels: points.map(point => point.label.replaceAll('_', ' ')),
                    datasets: [{
                        label: type === 'line' ? 'Cash revenue (Rs.)' : 'Orders',
                        data: points.map(point => Number(point.value)),
                        borderColor: '#079c55',
                        backgroundColor: circular ? ['#8c5dff', '#4c9dff', '#28cf87', '#9fe4c6', '#00a960', '#d8b44c', '#6276da', '#b3b9c5'] : '#079c5526',
                        borderWidth: circular ? 0 : 2,
                        fill: true,
                        tension: 0.35,
                    }],
                },
                options,
            }));
        });
    }
    refreshTheme();
})();
