<a class="back-link" href="<?= e(url('/orders')) ?>">← Back to orders</a>
<div class="page-heading">
    <div>
        <h1>Order <?= e($order['order_number']) ?></h1>
        <p>Placed on <?= e(date('d M Y, g:i A', strtotime($order['created_at']))) ?></p>
    </div>
    <a class="btn btn-outline-primary" href="<?= e(url('/support')) ?>">Need Help?</a>
</div>
<div class="order-layout">
    <section class="panel">
        <span class="badge status-<?= e($order['status']) ?>"><?= e(label($order['status'])) ?></span>
        <h2 class="mt-3">Laundry Journey</h2>
        <ol class="vertical-timeline">
            <?php foreach ($history as $event): ?>
                <li class="done">
                    <span>✓</span>
                    <div>
                        <strong><?= e(str_starts_with($event['notes'] ?? '', 'Cash receipt:') ? 'Payment Recorded' : label($event['status'])) ?></strong>
                        <small><?= e(date('d M, g:i A', strtotime($event['created_at']))) ?></small>
                        <?php if ($event['notes']): ?>
                            <p><?= e($event['notes']) ?></p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
            <?php if (!in_array($order['status'], ['delivered', 'cancelled'], true)): ?>
                <li>
                    <span>◷</span>
                    <div>
                        <strong>Next steps</strong>
                        <small>Updates appear here as your laundry progresses.</small>
                    </div>
                </li>
            <?php endif; ?>
        </ol>
    </section>
    <section class="panel">
        <h2>Order Details</h2>
        <dl class="order-details">
            <?php foreach (['Student' => $order['student_name'], 'Service' => $order['service_name'], 'Estimated weight' => $order['estimated_weight'] !== null ? $order['estimated_weight'] . ' kg' : 'Per item', 'Actual weight' => $order['actual_weight'] !== null ? $order['actual_weight'] . ' kg' : '—', 'Subtotal' => money($order['subtotal']), 'Discount' => money($order['discount_amount']), $order['status'] === 'pending' ? 'Estimated total' : 'Final total' => money($order['total_amount']), 'Paid' => money($order['paid']), 'Balance' => money(max(0, $order['total_amount'] - $order['paid'])), 'Pickup date' => $order['pickup_date'], 'Pickup slot' => $order['pickup_slot'], 'Hostel' => $order['hostel_name'], 'Room' => $order['room_number']] as $key => $value): ?>
                <div>
                    <dt><?= e($key) ?></dt>
                    <dd><?= e($value) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
        <?php if ($order['status'] !== 'cancelled'): ?>
            <div class="soft-callout">
                <p>Estimated turnaround: <?= e($order['estimated_time_hours'] ?? '48') ?> hours after collection. Processing times may vary.</p>
            </div>
        <?php endif; ?>
        <h3>Instructions</h3>
        <p><?= nl2br(e($order['special_instructions'] ?: 'No special instructions.')) ?></p>
        <?php foreach ($items as $item): ?>
            <p><?= e($item['name']) ?>: <?= e($item['quantity']) ?> × <?= e(money($item['unit_price'])) ?></p>
        <?php endforeach; ?>
        <?php if ($tags): ?>
            <h3>Bag Tags</h3>
            <div class="tag-list">
                <?php foreach ($tags as $tag): ?>
                    <span class="badge status-ready"><?= e($tag['tag_number']) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php if ($user['role'] !== 'student'): ?>
    <section class="panel mt-4">
        <h2>Order Actions</h2>
        <?php if ($order['status'] === 'pending'): ?>
            <form method="post" action="<?= e(url('/orders/' . $order['id'] . '/status')) ?>" class="stack-form">
                <?= csrf() ?>
                <input type="hidden" name="status" value="collected">
                <div class="form-grid three">
                    <label>
                        <?= $order['pricing_type'] === 'per_item' ? 'Confirmed item count' : 'Actual weight (kg)' ?>
                        <input name="quantity" type="number" min="<?= $order['pricing_type'] === 'per_item' ? '1' : '0.1' ?>" max="999" step="<?= $order['pricing_type'] === 'per_item' ? '1' : '0.01' ?>" required>
                    </label>
                    <label>
                        Number of bags
                        <input name="bags" type="number" min="1" max="25" value="1" required>
                    </label>
                    <label>
                        Bag tag / prefix
                        <input name="tag" maxlength="40" pattern="[A-Za-z0-9-]+" value="<?= e($order['order_number']) ?>" required>
                    </label>
                </div>
                <small class="muted">Each bag receives a unique tag. Final pricing uses the service rate accepted at booking.</small>
                <button class="btn btn-primary align-self-start">Confirm Collection</button>
            </form>
        <?php else: ?>
            <div class="button-row">
                <?php $next = ['collected' => 'at_laundry', 'at_laundry' => 'washing', 'washing' => 'ready', 'ready' => 'out_for_delivery', 'out_for_delivery' => 'delivered'][$order['status']] ?? null; ?>
                <?php if ($next && ($user['role'] === 'admin' || in_array($next, ['out_for_delivery', 'delivered'], true))): ?>
                    <form method="post" action="<?= e(url('/orders/' . $order['id'] . '/status')) ?>">
                        <?= csrf() ?>
                        <input type="hidden" name="status" value="<?= e($next) ?>">
                        <button class="btn btn-primary">Mark <?= e(label($next)) ?></button>
                    </form>
                <?php endif; ?>
                <?php if ($order['status'] !== 'cancelled' && (float) $order['paid'] < (float) $order['total_amount']): ?>
                    <form method="post" action="<?= e(url('/orders/' . $order['id'] . '/payment')) ?>" data-confirm="Confirm that you have physically received this cash payment?">
                        <?= csrf() ?><button class="btn btn-outline-primary">Confirm Cash Received · <?= e(money($order['total_amount'] - $order['paid'])) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($assignments): ?>
            <h3 class="mt-4">Assignments</h3>
            <?php foreach ($assignments as $assignment): ?>
                <p><?= e($assignment['task']) ?> · <?= e($assignment['name']) ?> · <?= e(label($assignment['status'])) ?></p>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($user['role'] === 'admin' && in_array($order['status'], ['pending', 'ready', 'out_for_delivery'], true)): ?>
            <form method="post" action="<?= e(url('/orders/' . $order['id'] . '/assign')) ?>" class="filter-bar mt-3">
                <?= csrf() ?>
                <input type="hidden" name="kind" value="<?= $order['status'] === 'pending' ? 'pickup' : 'delivery' ?>">
                <label>
                    Assign representative
                    <select name="representative_id" required>
                        <?php foreach ($representatives as $representative): ?>
                            <option value="<?= e($representative['id']) ?>"><?= e($representative['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="btn btn-outline-primary">Assign</button>
            </form>
            <?php if (!$representatives): ?>
                <p class="muted">Add an active representative for this hostel first.</p>
            <?php endif; ?>
        <?php endif; ?>
    </section>
<?php endif; ?>
<?php if ($order['status'] === 'pending' && $user['role'] !== 'representative'): ?>
    <form class="mt-4" method="post" action="<?= e(url('/orders/' . $order['id'] . '/status')) ?>" data-confirm="Cancel this pickup?">
        <?= csrf() ?>
        <input type="hidden" name="status" value="cancelled">
        <button class="btn btn-outline-danger">Cancel Order</button>
    </form>
<?php endif; ?>
<?php if ($payments): ?>
    <section class="panel mt-4">
        <h2>Payment Receipts</h2>
        <?php foreach ($payments as $payment): ?>
            <p>
                <strong><?= e(money($payment['amount'])) ?></strong> · <?= e(label($payment['method'])) ?> · <?= e(label($payment['status'])) ?> · <?= e($payment['paid_at']) ?><small class="d-block muted"><?= e($payment['transaction_id']) ?></small>
            </p>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
