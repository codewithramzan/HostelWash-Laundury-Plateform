<div class="page-heading">
    <div>
        <h1>Students & Users</h1>
        <p>Manage accounts without deleting order or financial history.</p>
    </div>
    <a class="btn btn-primary" href="#add-user">+ Add User</a>
</div>
<form method="get" class="filter-bar">
    <input name="q" aria-label="Search users" placeholder="Search name or email" value="<?= e($search) ?>">
    <button class="btn btn-outline-primary">Search</button>
</form>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Role</th>
                    <th>Orders</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $person): ?>
                    <tr>
                        <td>
                            <strong><?= e($person['name']) ?></strong>
                        </td>
                        <td><?= e($person['email']) ?><small><?= e($person['phone']) ?></small>
                        </td>
                        <td><?= e(label($person['role'])) ?></td>
                        <td><?= e($person['orders']) ?></td>
                        <td>
                            <span class="badge status-<?= $person['status'] === 'active' ? 'ready' : 'cancelled' ?>"><?= e(label($person['status'])) ?></span>
                        </td>
                        <td>
                            <div class="button-row">
                                <a href="?view=<?= e($person['id']) ?>" class="btn btn-sm btn-outline-primary">Profile</a>
                                <?php if ($person['id'] != $user['id']): ?>
                                    <form method="post" action="<?= e(url('/admin/users')) ?>">
                                        <?= csrf() ?>
                                        <input type="hidden" name="id" value="<?= e($person['id']) ?>">
                                        <input type="hidden" name="status" value="<?= $person['status'] === 'active' ? 'inactive' : 'active' ?>">
                                        <button class="btn btn-sm btn-outline-primary"><?= $person['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require ROOT . '/app/Views/partials/pagination.php'; ?>
<?php if ($detail): ?>
    <section class="panel mt-4">
        <h2><?= e($detail['name']) ?></h2>
        <p><?= e($detail['email']) ?> · <?= e($detail['phone']) ?></p>
        <h3>Recent Order History</h3>
        <?php foreach ($orders as $order): ?>
            <p>
                <a href="<?= e(url('/orders/' . $order['id'])) ?>"><?= e($order['order_number']) ?></a> · <?= e(label($order['status'])) ?> · <?= e(money($order['total_amount'])) ?></p>
        <?php endforeach; ?>
        <?php if (!$orders): ?>
            <p>No orders yet.</p>
        <?php endif; ?>
        <h3>Recent Payment History</h3>
        <?php foreach ($payments as $payment): ?>
            <p><?= e($payment['order_number']) ?> · <?= e(money($payment['amount'])) ?> · <?= e(label($payment['status'])) ?></p>
        <?php endforeach; ?>
        <?php if (!$payments): ?>
            <p>No payments yet.</p>
        <?php endif; ?>
        <h3>Support Requests</h3>
        <?php foreach ($complaints as $complaint): ?>
            <p>
                <a href="<?= e(url('/support')) ?>"><?= e($complaint['subject']) ?></a> · <?= e(label($complaint['status'])) ?></p>
        <?php endforeach; ?>
        <?php if (!$complaints): ?>
            <p>No support requests.</p>
        <?php endif; ?>
    </section>
<?php endif; ?>
<section class="panel mt-4" id="add-user">
    <h2>Add User</h2>
    <form method="post" action="<?= e(url('/admin/users')) ?>" class="stack-form">
        <?= csrf() ?><div class="form-grid">
            <label>
                Full name
                <input name="name" maxlength="100" required>
            </label>
            <label>
                Email
                <input name="email" type="email" maxlength="100" required>
            </label>
            <label>
                Phone <span class="muted">(optional)</span>
                <input name="phone" maxlength="20" type="tel">
            </label>
            <label>
                Role
                <select name="role">
                    <option value="student">Student</option>
                    <option value="representative">Representative</option>
                    <option value="admin">Admin</option>
                </select>
            </label>
            <label>
                Initial password
                <input name="password" type="password" minlength="10" maxlength="72" autocomplete="new-password" required>
            </label>
        </div>
        <small class="muted">Share credentials privately. Ask the user to change their password in Profile. Assign representative accounts to hostels after creating them.</small>
        <button class="btn btn-primary align-self-start">Create Account</button>
    </form>
</section>
