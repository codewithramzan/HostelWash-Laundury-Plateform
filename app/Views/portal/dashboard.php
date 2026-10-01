<div class="page-heading">
    <div>
        <h1><?= $user['role'] === 'admin' ? 'Dashboard' : 'Hello, ' . e(explode(' ', $user['name'])[0]) . '!' ?></h1>
        <p><?= $user['role'] === 'representative' ? 'Your assigned pickups and deliveries, in one place.' : 'Here’s your laundry overview.' ?></p>
    </div>
    <a class="btn btn-primary" href="<?= e(url($user['role'] === 'student' ? '/orders/new' : '/orders')) ?>"><?= $user['role'] === 'student' ? '+ New Order' : 'View Orders →' ?></a>
</div>
<div class="stat-grid">
    <?php
$cards = $user['role'] === 'admin'
    ? [['▤', $stats['orders'], 'Total Orders'], ['₨', money($stats['revenue']), 'Cash Revenue'], ['♙', $stats['students'], 'Active Students'], ['▱', $stats['weight'] . ' kg', 'Laundry Collected']]
    : [['♙', $stats['active'], 'Active Orders'], ['▤', $stats['orders'], 'Total Orders'], ['₨', money($stats['revenue']), $user['role'] === 'student' ? 'Total Paid' : 'Paid on Assigned Orders']];
foreach ($cards as [$symbol, $value, $caption]): ?>
    <article class="stat-card">
        <span class="round-icon"><?= icon($symbol) ?></span>
        <div>
            <strong><?= e($value) ?></strong>
            <small><?= e($caption) ?></small>
        </div>
    </article>
<?php endforeach; ?>
</div>
<?php if ($user['role'] === 'admin'): ?>
    <div class="dashboard-grid">
        <section class="panel">
            <h2>Revenue Overview</h2>
            <p class="muted">Cash received · last 12 months</p>
            <div class="chart-box">
                <canvas aria-label="Monthly cash revenue" role="img" data-chart="line" data-points="<?= e(json_encode($revenueChart)) ?>">
                </canvas>
            </div>
            <?php if (!$revenueChart): ?>
                <p class="muted">No receipts in this period.</p>
            <?php endif; ?>
        </section>
        <section class="panel">
            <h2>Orders by Status</h2>
            <div class="chart-box">
                <canvas aria-label="Order status counts" role="img" data-chart="doughnut" data-points="<?= e(json_encode($statusChart)) ?>">
                </canvas>
            </div>
            <div class="chart-legend">
                <?php foreach ($statusChart as $point): ?>
                    <span><?= e(label($point['label'])) ?> <strong><?= e($point['value']) ?></strong>
                    </span>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
<?php else: ?>
    <div class="dashboard-grid">
        <section class="panel current-order">
            <div class="panel-heading">
                <h2><?= $current ? 'Current Order ' . e($current['order_number']) : 'Ready for a fresh start?' ?></h2>
                <?php if ($current): ?>
                    <span class="badge status-<?= e($current['status']) ?>"><?= e(label($current['status'])) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($current): ?>
                <div class="horizontal-timeline">
                    <?php foreach (['pending' => 'Placed', 'collected' => 'Collected', 'washing' => 'Washing', 'ready' => 'Ready', 'delivered' => 'Delivered'] as $stage => $name): ?>
                        <div class="<?= array_search($current['status'], app\Models\Order::STATUSES) >= array_search($stage, app\Models\Order::STATUSES) ? 'done' : '' ?>">
                            <span>✓</span>
                            <small><?= e($name) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="detail-grid">
                    <div>
                        <strong><?= e($current['actual_weight'] ?? $current['estimated_weight'] ?? 'Per item') ?><?= $current['pricing_type'] === 'per_kg' ? ' kg' : '' ?></strong>
                        <small><?= $current['actual_weight'] !== null ? 'Actual weight' : 'Estimate / pricing' ?></small>
                    </div>
                    <div>
                        <strong><?= e($current['service_name']) ?></strong>
                        <small>Service</small>
                    </div>
                    <div>
                        <strong><?= e(money($current['total_amount'])) ?></strong>
                        <small><?= $current['status'] === 'pending' ? 'Estimated total' : 'Total amount' ?></small>
                    </div>
                </div>
                <a class="btn btn-outline-primary float-end" href="<?= e(url('/orders/' . $current['id'])) ?>">View Details</a>
            <?php else: ?>
                <p>No active orders. You’re all caught up.</p>
                <a class="btn btn-primary" href="<?= e(url($user['role'] === 'student' ? '/orders/new' : '/orders')) ?>"><?= $user['role'] === 'student' ? 'Schedule a Pickup' : 'See Assigned Work' ?></a>
            <?php endif; ?>
        </section>
        <section class="panel">
            <h2>Good to know</h2>
            <p>Keep your bag ready before the pickup slot. Add special care instructions when booking.</p>
            <div class="soft-callout">
                <span>♧</span>
                <p>
                    <strong>Every step, right here.</strong>
                    <br>Your order timeline updates as the team cares for your laundry.</p>
            </div>
            <a class="text-link" href="<?= e(url('/notifications')) ?>">View Notifications →</a>
        </section>
    </div>
<?php endif; ?>
<section class="panel">
    <div class="panel-heading">
        <h2>Recent Orders</h2>
        <a class="text-link" href="<?= e(url('/orders')) ?>">View All →</a>
    </div>
    <?php $orders = array_slice($recent, 0, 5); require ROOT . '/app/Views/partials/order-table.php'; ?>
</section>
