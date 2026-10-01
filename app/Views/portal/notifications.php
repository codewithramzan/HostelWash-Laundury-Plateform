<div class="page-heading">
    <div>
        <h1>Notifications</h1>
        <p>The latest 100 updates from HostelWash.</p>
    </div>
    <form method="post" action="<?= e(url('/notifications/read')) ?>">
        <?= csrf() ?><button class="btn btn-outline-primary">Mark All Read</button>
    </form>
</div>
<div class="notification-list">
    <?php foreach ($notifications as $notification): ?>
        <article class="panel notification <?= $notification['is_read'] ? '' : 'unread' ?>">
            <span class="round-icon"><?= icon('♧') ?></span>
            <div>
                <h2><?= e($notification['title']) ?></h2>
                <p><?= e($notification['message']) ?></p>
                <small class="muted"><?= e($notification['created_at']) ?> · <?= $notification['is_read'] ? 'Read' : 'Unread' ?></small>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (!$notifications): ?>
        <div class="empty-state">
            <h2>You’re all caught up.</h2>
            <p>Your order updates will appear here.</p>
        </div>
    <?php endif; ?>
</div>
