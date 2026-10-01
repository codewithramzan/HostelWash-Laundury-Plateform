<?php if ($page === 'home'): ?>
    <section class="hero">
        <div class="hero-copy">
            <div class="eco-pill">
                <span>♧</span>
                <div>
                    Clean Campus<small>Greener Tomorrow</small>
                </div>
            </div>
            <h1><?= e($settings['headline']) ?></h1>
            <p><?= e($settings['intro']) ?></p>
            <div class="button-row">
                <a class="btn btn-primary" href="<?= e(url($user ? '/orders/new' : '/register')) ?>">Book Your Laundry <span>→</span>
                </a>
                <a class="btn btn-outline-primary" href="<?= e(url('/pricing')) ?>">View Pricing</a>
            </div>
            <div class="hero-note">
                <span class="tiny-dot">
                </span> Made for your hostel life</div>
        </div>
        <div class="hero-art" role="img" aria-label="Washing machine, fresh folded towels and a green plant">
            
        </div>
    </section>
    <section class="benefit-strip" aria-label="Service benefits">
        <?php foreach (['⌂' => ['Door Pickup', 'From your hostel'], '◷' => ['Convenient Delivery', 'Track every stage'], '₨' => ['Affordable', 'Clear pricing'], '♧' => ['Careful Handling', 'Every load matters']] as $symbol => [$name, $caption]): ?>
            <div>
                <span class="round-icon"><?= icon($symbol) ?></span>
                <p>
                    <strong><?= e($name) ?></strong>
                    <small><?= e($caption) ?></small>
                </p>
            </div>
        <?php endforeach; ?>
    </section>
    <div class="section-heading">
        <span class="eyebrow">LAUNDRY, SIMPLIFIED</span>
        <h2>A fresh start, every time.</h2>
        <p>Choose the care your clothes need. We’ll handle the rest.</p>
    </div>
    <?php require ROOT . '/app/Views/partials/services.php'; ?>
    <div class="section-heading">
        <h2>From your door. Back to your door.</h2>
    </div>
    <?php require ROOT . '/app/Views/partials/steps.php'; ?>
<?php elseif (in_array($page, ['services', 'pricing'], true)): ?>
    <div class="section-heading">
        <span class="eyebrow">CLEAN CLOTHES. CLEAR PRICES.</span>
        <h1><?= e($title) ?></h1>
        <p><?= $page === 'services' ? 'Professional cleaning care for students.' : 'Transparent pricing for every load.' ?></p>
    </div>
    <?php require ROOT . '/app/Views/partials/services.php'; ?>
    <div class="soft-banner">
        <div>
            <h3>Care that fits your routine.</h3>
            <p>Per-kilogram totals are finalized when your laundry is weighed at pickup.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(url('/orders/new')) ?>">Schedule Pickup →</a>
    </div>
<?php elseif ($page === 'how-it-works'): ?>
    <div class="section-heading">
        <h1>How It Works</h1>
        <p>Get your laundry cleaned in 4 simple steps.</p>
    </div>
    <?php require ROOT . '/app/Views/partials/steps.php'; ?>
    <div class="soft-banner">
        <div>
            <h2>Relax, We Handle the Rest!</h2>
            <p>Track your orders from collection to delivery.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(url('/orders/new')) ?>">Book Now →</a>
    </div>
<?php elseif ($page === 'hostels'): ?>
    <div class="section-heading">
        <h1>Right here at your hostel.</h1>
        <p>Find the locations currently served by HostelWash.</p>
    </div>
    <div class="card-grid">
        <?php foreach ($hostels as $hostel): ?>
            <article class="panel">
                <span class="round-icon"><?= icon('⌂') ?></span>
                <h2><?= e($hostel['name']) ?></h2>
                <p><?= e($hostel['description']) ?></p>
                <p><?= e($hostel['address']) ?></p>
                <span class="badge status-ready"><?= e($hostel['rooms']) ?> active rooms</span>
                <a class="text-link" href="<?= e(url('/orders/new')) ?>">Schedule pickup →</a>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (!$hostels): ?>
        <div class="empty-state">
            <h2>Hostel registration is opening soon.</h2>
            <p>Contact the team for availability at your hostel.</p>
        </div>
    <?php endif; ?>
<?php elseif ($page === 'faq'): ?>
    <div class="section-heading">
        <h1>A few things you might be wondering.</h1>
        <p>Everything you need before your first pickup.</p>
    </div>
    <div class="reading-width">
        <?php foreach ([
    'How do I book a pickup?' => 'Create a student account, choose your hostel and room, select a service, and pick an available date and slot.',
    'How is my final bill calculated?' => 'Per-kilogram services use the actual weight confirmed at collection. Iron-only services use the confirmed item count. Your accepted service rate is saved when booking.',
    'When will I receive my laundry?' => 'Each service displays an estimated turnaround. You can follow collection, processing and delivery in My Orders. These times are estimates, not guaranteed appointments.',
    'Can I cancel my order?' => 'Yes. Open the order and cancel while it is still pending, before collection.',
    'How can I pay?' => 'Pay cash to authorized staff after your final amount is confirmed. Your payment receipt appears in your account once recorded.',
    'What about delicate clothes?' => 'Include care instructions when booking and explain any special requirements to the representative before handing over your laundry.',
    'How do I report a problem?' => 'Open Support in your account and link your request to the relevant order. The team can reply and update its status.',
] as $question => $answer): ?>
            <details class="faq-item">
                <summary><?= e($question) ?></summary>
                <p><?= e($answer) ?></p>
            </details>
        <?php endforeach; ?>
    </div>
<?php elseif ($page === 'contact'): ?>
    <div class="section-heading">
        <h1>We’re here to help.</h1>
        <p>Questions about your laundry? Let’s get them sorted.</p>
    </div>
    <div class="card-grid two">
        <article class="panel">
            <span class="round-icon"><?= icon('◉') ?></span>
            <h2>Order support</h2>
            <p>Send a message from your account to keep your order and conversation together.</p>
            <a class="btn btn-primary" href="<?= e(url('/support')) ?>">Open Support</a>
        </article>
        <article class="panel">
            <span class="round-icon">✉</span>
            <h2>Talk to our team</h2>
            <?php if ($settings['contact_email']): ?>
                <p>
                    <a href="mailto:<?= e($settings['contact_email']) ?>"><?= e($settings['contact_email']) ?></a>
                </p>
            <?php endif; ?>
            <?php if ($settings['contact_phone']): ?>
                <p><?= e($settings['contact_phone']) ?></p>
            <?php endif; ?>
            <?php if (!$settings['contact_email'] && !$settings['contact_phone']): ?>
                <p>Contact your hostel representative, or send a request through your account.</p>
            <?php endif; ?>
        </article>
    </div>
<?php else: ?>
    <div class="section-heading">
        <h1><?= e($title) ?></h1>
    </div>
    <article class="panel reading-width prose">
        <?= nl2br(e($settings[$page])) ?></article>
<?php endif; ?>
