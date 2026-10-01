<div class="service-grid">
    <?php foreach ($services as $index => $service): ?>
        <article class="service-card <?= $index === 1 ? 'featured' : '' ?>">
            <?php if ($index === 1): ?>
                <div class="popular-pill">
                    Everyday Favorite</div>
            <?php endif; ?>
            <div class="service-photo photo-<?= $index % 3 ?>" role="img" aria-label="<?= e($service['name']) ?> laundry care">
            </div>
            <h2><?= e($service['name']) ?></h2>
            <div class="service-price">
                <?= e(money($service['price'])) ?><small>/<?= $service['pricing_type'] === 'per_kg' ? 'kg' : 'item' ?></small>
            </div>
            <p><?= e($service['description']) ?></p>
            <ul class="check-list">
                <li>Careful handling</li>
                <li>Hostel pickup & delivery</li>
                <li>Order tracking included</li>
                <li>Estimated <?= e($service['estimated_time_hours'] ?? '48') ?>-hour turnaround</li>
            </ul>
            <a class="btn <?= $index === 1 ? 'btn-primary' : 'btn-outline-primary' ?> w-100" href="<?= e(url('/orders/new')) ?>">Book Now</a>
        </article>
    <?php endforeach; ?>
</div>
<?php if (!$services): ?>
    <div class="empty-state">
        Services are being prepared. Please check back soon.</div>
<?php endif; ?>
