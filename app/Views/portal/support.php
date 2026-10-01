<div class="page-heading">
    <div>
        <h1>Support</h1>
        <p>Let’s get your laundry questions sorted.</p>
    </div>
</div>
<?php if ($user['role'] !== 'admin'): ?>
    <form method="post" action="<?= e(url('/support')) ?>" class="panel stack-form mb-4">
        <?= csrf() ?><h2>New Support Request</h2>
        <div class="form-grid">
            <label>
                Subject
                <input name="subject" maxlength="100" required>
            </label>
            <label>
                Related order
                <select name="order_id">
                    <option value="">General question</option>
                    <?php foreach ($orders as $order): ?>
                        <option value="<?= e($order['id']) ?>"><?= e($order['order_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>
            Message<textarea name="message" rows="4" maxlength="5000" required></textarea>
        </label>
        <button class="btn btn-primary align-self-start">Send Request</button>
    </form>
<?php endif; ?>
<form method="get" class="filter-bar">
    <label>
        Request status
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach (['open', 'in_progress', 'resolved'] as $option): ?>
                <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(label($option)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="btn btn-outline-primary">Filter</button>
</form>
<?php foreach ($complaints as $complaint): ?>
    <article class="panel mb-3">
        <div class="panel-heading">
            <h2><?= e($complaint['subject']) ?></h2>
            <span class="badge status-<?= $complaint['status'] === 'resolved' ? 'ready' : 'pending' ?>"><?= e(label($complaint['status'])) ?></span>
        </div>
        <small class="muted"><?= e($complaint['name']) ?> · <?= e($complaint['created_at']) ?></small>
        <?php if ($complaint['order_id']): ?>
            <a class="text-link" href="<?= e(url('/orders/' . $complaint['order_id'])) ?>">Related order →</a>
        <?php endif; ?>
        <p class="mt-3"><?= nl2br(e($complaint['message'])) ?></p>
        <?php foreach ($replies[$complaint['id']] ?? [] as $reply): ?>
            <div class="soft-callout">
                <p>
                    <strong><?= e($reply['name']) ?> · Support</strong>
                    <br><?= nl2br(e($reply['message'])) ?><small class="d-block"><?= e($reply['created_at']) ?></small>
                </p>
            </div>
        <?php endforeach; ?>
        <?php if ($user['role'] === 'admin'): ?>
            <form method="post" action="<?= e(url('/support/' . $complaint['id'])) ?>" class="stack-form">
                <?= csrf() ?><label>
                    Reply<textarea name="reply" rows="2" maxlength="5000"></textarea>
                </label>
                <div class="filter-bar">
                    <label>
                        Status
                        <select name="status">
                            <?php foreach (['open', 'in_progress', 'resolved'] as $option): ?>
                                <option value="<?= e($option) ?>" <?= $complaint['status'] === $option ? 'selected' : '' ?>><?= e(label($option)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="btn btn-primary">Update Request</button>
                </div>
            </form>
        <?php endif; ?>
    </article>
<?php endforeach; ?>
<?php if (!$complaints): ?>
    <div class="empty-state">
        <h2>No support requests.</h2>
        <p>Requests and replies will appear here.</p>
    </div>
<?php endif; ?>
