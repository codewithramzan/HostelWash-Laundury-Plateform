<?php $pageSize = $pageSize ?? 20; $pageCount = max(1, (int) ceil($total / $pageSize)); ?>
<div class="pagination-bar">
    <span><?= e($total) ?> records · Page <?= e($page) ?> of <?= e($pageCount) ?></span>
    <div>
        <?php if ($page > 1): ?>
            <a class="btn btn-sm btn-outline-primary" href="?<?= e(http_build_query(array_replace($_GET, ['page' => $page - 1]))) ?>">← Previous</a>
        <?php endif; ?>
        <?php if ($page < $pageCount): ?>
            <a class="btn btn-sm btn-outline-primary" href="?<?= e(http_build_query(array_replace($_GET, ['page' => $page + 1]))) ?>">Next →</a>
        <?php endif; ?>
    </div>
</div>
