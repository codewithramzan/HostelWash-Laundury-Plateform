<div class="page-heading">
    <div>
        <h1><?= e($title) ?></h1>
        <p><?= $user['role'] === 'representative' ? 'Only tasks assigned to your account appear here.' : 'Every load, from collection to delivery.' ?></p>
    </div>
    <?php if ($user['role'] === 'student'): ?>
        <a class="btn btn-primary" href="<?= e(url('/orders/new')) ?>">+ New Order</a>
    <?php endif; ?>
</div>
<form method="get" class="filter-bar">
    <label class="sr-only" for="order-search">
        Search orders</label>
    <input id="order-search" name="q" value="<?= e($search) ?>" placeholder="Search order, student or room">
    <label class="sr-only" for="status-filter">
        Order status</label>
    <select name="status" id="status-filter">
        <option value="">All statuses</option>
        <?php foreach (app\Models\Order::STATUSES as $option): ?>
            <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(label($option)) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-primary">Filter</button>
    <a class="btn btn-outline-primary" href="<?= e(url('/orders')) ?>">Clear</a>
</form>
<?php if ($user['role'] === 'representative'): ?>
    <div class="pickup-grid">
        <?php foreach ($orders as $row): ?>
            <article class="panel pickup-card">
                <div class="panel-heading">
                    <span class="round-icon"><?= icon('♙') ?></span>
                    <span class="badge status-<?= e($row['status']) ?>"><?= e(label($row['status'])) ?></span>
                </div>
                <h2>Room <?= e($row['room_number']) ?></h2>
                <strong><?= e($row['student_name']) ?></strong>
                <p><?= e($row['hostel_name']) ?><br><?= e($row['pickup_date']) ?> · <?= e($row['pickup_slot']) ?></p>
                <p><?= e($row['service_name']) ?> · <?= e(money($row['total_amount'])) ?></p>
                <a class="btn btn-primary w-100" href="<?= e(url('/orders/' . $row['id'])) ?>"><?= $row['status'] === 'pending' ? 'Open Collection' : 'View Order' ?></a>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (!$orders): ?>
        <div class="empty-state">
            <h2>No assignments match.</h2>
            <p>New assigned pickups and deliveries will appear here.</p>
        </div>
    <?php endif; ?>
<?php else: ?>
    <section class="panel">
        <?php require ROOT . '/app/Views/partials/order-table.php'; ?>
    </section>
<?php endif; ?>
<?php require ROOT . '/app/Views/partials/pagination.php'; ?>
