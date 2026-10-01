<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Order</th>
                <th>Student / location</th>
                <th>Service</th>
                <th>Pickup</th>
                <th>Status</th>
                <th>Amount</th>
                <th>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $row): ?>
                <tr>
                    <td>
                        <a class="order-link" href="<?= e(url('/orders/' . $row['id'])) ?>"><?= e($row['order_number']) ?></a>
                    </td>
                    <td>
                        <strong><?= e($row['student_name']) ?></strong>
                        <small><?= e($row['hostel_name']) ?> · Room <?= e($row['room_number']) ?></small>
                    </td>
                    <td><?= e($row['service_name']) ?></td>
                    <td><?= e($row['pickup_date']) ?><small><?= e($row['pickup_slot']) ?></small>
                    </td>
                    <td>
                        <span class="badge status-<?= e($row['status']) ?>"><?= e(label($row['status'])) ?></span>
                    </td>
                    <td><?= e(money($row['total_amount'])) ?><small><?= (float) $row['paid'] >= (float) $row['total_amount'] ? 'Paid' : 'Payment due' ?></small>
                    </td>
                    <td>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/orders/' . $row['id'])) ?>">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (!$orders): ?>
    <div class="empty-state">
        <span class="round-icon">▤</span>
        <h3>No orders yet</h3>
        <p>Your laundry orders will appear here.</p>
    </div>
<?php endif; ?>
