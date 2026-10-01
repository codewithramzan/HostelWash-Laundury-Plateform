<div class="page-heading">
    <div>
        <h1>Payments</h1>
        <p>Your recorded cash receipts. Online payment is not enabled.</p>
    </div>
</div>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Paid at</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('/orders/' . $payment['order_id'])) ?>"><?= e($payment['order_number']) ?></a>
                        </td>
                        <td><?= e(money($payment['amount'])) ?></td>
                        <td><?= e(label($payment['method'])) ?></td>
                        <td>
                            <span class="badge status-ready"><?= e(label($payment['status'])) ?></span>
                        </td>
                        <td><?= e($payment['paid_at'] ?? '—') ?></td>
                        <td class="muted"><?= e($payment['transaction_id'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$payments): ?>
        <div class="empty-state">
            <h3>No payments recorded yet.</h3>
            <p>Staff record your cash payment once the final amount is confirmed.</p>
        </div>
    <?php endif; ?>
</section>
<?php $pageSize = 30; require ROOT . '/app/Views/partials/pagination.php'; ?>
