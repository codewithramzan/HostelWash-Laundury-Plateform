(() => {
    'use strict';

    let stored = null;
    try {
        stored = localStorage.getItem('hostelwash.theme');
    } catch (_) {
        // Browser privacy settings may disable persistent storage.
    }

    const preferred = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    document.documentElement.dataset.theme = ['light', 'dark'].includes(stored) ? stored : preferred;
})();
