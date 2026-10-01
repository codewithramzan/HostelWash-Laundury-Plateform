<?php
$portal = $user && (str_starts_with($path ?? $_SERVER['REQUEST_URI'] ?? '', '/admin')
    || in_array(explode('/', trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/'))[0],
        ['dashboard', 'orders', 'payments', 'profile', 'support', 'notifications'], true));
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
?>
<!doctype html>
<html lang="en" data-theme="light">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="HostelWash — convenient laundry pickup, care and delivery for university hostels.">
        <meta name="theme-color" content="#008f48">
        <title><?= e($title ?? 'HostelWash') ?> · HostelWash</title>
        <link rel="icon" href="<?= e(url('/assets/images/favicon.svg')) ?>" type="image/svg+xml">
        <script src="<?= e(url('/assets/js/theme.js')) ?>">
        </script>
        <link rel="stylesheet" href="<?= e(url('/assets/vendor/bootstrap.min.css')) ?>">
        <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
        <script src="<?= e(url('/assets/vendor/chart.umd.js')) ?>" defer>
        </script>
        <script src="<?= e(url('/assets/js/app.js')) ?>" defer>
        </script>
    </head>
    <body class="<?= $portal ? 'portal-body' : 'public-body' ?>">
        <a class="skip-link" href="#main">Skip to content</a>
        <?php if ($portal): ?>
            <button class="sidebar-shade" aria-label="Close navigation" tabindex="-1">
            </button>
            <aside class="sidebar" id="sidebar" aria-label="Main navigation">
                <a class="brand" href="<?= e(url('/dashboard')) ?>">
                    <img src="<?= e(url('/assets/images/favicon.svg')) ?>" alt="">HostelWash</a>
                <div class="sidebar-label">
                    <?= e(label($user['role'])) ?> workspace</div>
                <nav class="sidebar-menu">
                    <?php
            $navigation = ['Dashboard' => ['/dashboard', '⌂']];
            if ($user['role'] === 'student') {
                $navigation += ['New Order' => ['/orders/new', '+'], 'My Orders' => ['/orders', '▤']];
            } elseif ($user['role'] === 'representative') {
                $navigation += ['Pickups & Delivery' => ['/orders', '▤']];
            } else {
                $navigation += ['Orders' => ['/orders', '▤'], 'Students & Users' => ['/admin/users', '♧'],
                    'Hostels' => ['/admin/hostels', '▦'], 'Rooms' => ['/admin/rooms', '▥'],
                    'Representatives' => ['/admin/representatives', '♙'], 'Services & Pricing' => ['/admin/services', '◇']];
            }
            $navigation += ['Payments' => ['/payments', '▣']];
            if ($user['role'] === 'admin') {
                $navigation += ['Expenses' => ['/admin/expenses', '↗'], 'Coupons' => ['/admin/coupons', '♧'],
                    'Reports' => ['/admin/reports', '▥']];
            }
            $navigation += ['Profile' => ['/profile', '♙'], 'Support' => ['/support', '◉']];
            if ($user['role'] === 'admin') {
                $navigation += ['Settings' => ['/admin/settings', '⚙'], 'Website Content' => ['/admin/content', '▧']];
            }
            foreach ($navigation as $name => [$href, $symbol]): ?>
                    <a class="nav-item <?= $currentPath === parse_url(url($href), PHP_URL_PATH) ? 'active' : '' ?>" href="<?= e(url($href)) ?>">
                        <span class="nav-symbol" aria-hidden="true"><?= icon($symbol) ?></span>
                        <?= e($name) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="<?= e(url('/')) ?>">↗ Visit website</a>
                <form method="post" action="<?= e(url('/logout')) ?>">
                    <?= csrf() ?><button class="logout">↪ Logout</button>
                </form>
            </div>
        </aside>
        <div class="portal-shell">
            <header class="topbar">
                <button class="icon-button menu-toggle" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">☰</button>
                <span class="mobile-brand">HostelWash</span>
                <div class="topbar-actions">
                    <button class="theme-toggle" type="button" aria-label="Toggle color theme">☀ Light</button>
                    <a class="icon-button" href="<?= e(url('/notifications')) ?>" aria-label="Notifications"><?= icon('bell') ?></a>
                    <a class="profile-chip" href="<?= e(url('/profile')) ?>">
                        <span class="avatar"><?= e(mb_substr($user['name'], 0, 1)) ?></span>
                        <span><?= e(explode(' ', $user['name'])[0]) ?></span>
                    </a>
                </div>
            </header>
            <main id="main" class="portal-main">
            <?php else: ?>
                <header class="public-header">
                    <a class="brand" href="<?= e(url('/')) ?>">
                        <img src="<?= e(url('/assets/images/favicon.svg')) ?>" alt="">HostelWash</a>
                    <button class="icon-button public-menu-toggle" aria-controls="public-nav" aria-expanded="false" aria-label="Toggle navigation">☰</button>
                    <nav class="public-nav" id="public-nav" aria-label="Public navigation">
                        <?php foreach (['Home' => '/', 'Services' => '/services', 'How It Works' => '/how-it-works', 'Pricing' => '/pricing', 'Hostels' => '/hostels', 'FAQ' => '/faq'] as $name => $href): ?>
                            <a class="<?= $currentPath === parse_url(url($href), PHP_URL_PATH) ? 'active' : '' ?>" href="<?= e(url($href)) ?>"><?= e($name) ?></a>
                        <?php endforeach; ?>
                    </nav>
                    <div class="public-actions">
                        <button class="theme-toggle" type="button">☀ Light</button>
                        <?php if ($user): ?>
                            <a class="btn btn-primary" href="<?= e(url('/dashboard')) ?>">Dashboard</a>
                        <?php else: ?>
                            <a class="btn btn-outline-primary" href="<?= e(url('/login')) ?>">Login</a>
                            <a class="btn btn-primary signup-link" href="<?= e(url('/register')) ?>">Sign Up</a>
                        <?php endif; ?>
                    </div>
                </header>
                <main id="main" class="public-main">
                <?php endif; ?>
                <?php if (isset($_SESSION['flash'])): $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
                <div class="notice <?= e($flash['type']) ?>" role="status">
                    <?= e($flash['message']) ?><button type="button" class="dismiss-notice" aria-label="Dismiss notification">×</button>
                </div>
            <?php endif; ?>
            <?php require $contentTemplate; ?>
        </main>
        <?php if ($portal): ?>
            <footer class="portal-footer">
                HostelWash · A little care for your everyday.</footer>
        </div>
    <?php else: ?>
        <footer class="public-footer">
            <div>
                <a class="brand" href="<?= e(url('/')) ?>">
                    <img src="<?= e(url('/assets/images/favicon.svg')) ?>" alt="">HostelWash</a>
                <p>Fresh laundry. More time for you.</p>
            </div>
            <div class="footer-links">
                <?php foreach (['About' => 'about', 'Contact' => 'contact', 'Terms' => 'terms', 'Privacy' => 'privacy'] as $name => $href): ?>
                    <a href="<?= e(url('/' . $href)) ?>"><?= e($name) ?></a>
                <?php endforeach; ?>
            </div>
            <small>© <?= date('Y') ?> HostelWash</small>
        </footer>
    <?php endif; ?>
</body>
</html>
