<div class="page-heading">
    <div>
        <h1><?= e($title) ?></h1>
        <p>Manage your <?= e(strtolower($title)) ?>.</p>
    </div>
    <a href="#record-form" class="btn btn-primary"><?= $edit ? 'Edit Record' : '+ Add Record' ?></a>
</div>
<form method="get" class="filter-bar">
    <label class="sr-only" for="resource-search">
        Search</label>
    <input id="resource-search" name="q" value="<?= e($search) ?>" placeholder="Search <?= e(strtolower($title)) ?>">
    <?php if ($resource === 'expenses'): ?>
        <label>
            From
            <input type="date" name="from" value="<?= e($_GET['from'] ?? '') ?>">
        </label>
        <label>
            To
            <input type="date" name="to" value="<?= e($_GET['to'] ?? '') ?>">
        </label>
        <input name="category" placeholder="Category" value="<?= e($_GET['category'] ?? '') ?>">
    <?php endif; ?>
    <button class="btn btn-outline-primary">Search</button>
    <a href="<?= e(url('/admin/' . $resource)) ?>" class="text-link">Clear</a>
</form>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <?php foreach ($definition['fields'] as $column => [$name]): ?>
                        <th><?= e($name) ?></th>
                    <?php endforeach; ?>
                    <?php if ($resource === 'coupons'): ?>
                        <th>Used</th>
                    <?php endif; ?>
                    <?php if ($resource === 'representatives'): ?>
                        <th>Workload</th>
                    <?php endif; ?>
                    <th>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record): ?>
                    <tr>
                        <td>#<?= e($record['id']) ?></td>
                        <?php foreach ($definition['fields'] as $column => [$name, $type, $rule]): ?>
                            <td>
                                <?php if ($type === 'lookup'): ?>
                                    <?= e(array_column($options[$column], 'name', 'id')[$record[$column]] ?? $record[$column]) ?>
                                <?php elseif (in_array($column, ['status', 'is_active'], true)): ?>
                                    <span class="badge status-<?= in_array((string) $record[$column], ['active', '1'], true) ? 'ready' : 'cancelled' ?>"><?= e($column === 'is_active' ? ($record[$column] ? 'Active' : 'Inactive') : label($record[$column])) ?></span>
                                <?php elseif ($type === 'money'): ?>
                                    <?= e(number_format((float) $record[$column], 2)) ?>
                                <?php else: ?>
                                    <?= e($record[$column] ?? '—') ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <?php if ($resource === 'coupons'): ?>
                            <td><?= e($record['used_count']) ?></td>
                        <?php endif; ?>
                        <?php if ($resource === 'representatives'): ?>
                            <td><?= e($workload[$record['id']]['pickups'] ?? 0) ?> pickups<br><?= e($workload[$record['id']]['deliveries'] ?? 0) ?> deliveries</td>
                        <?php endif; ?>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="?edit=<?= e($record['id']) ?>#record-form">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$records): ?>
        <div class="empty-state">
            <h3>No records yet.</h3>
            <p>Add your first record below.</p>
        </div>
    <?php endif; ?>
</section>
<?php require ROOT . '/app/Views/partials/pagination.php'; ?>
<section class="panel mt-4" id="record-form">
    <h2><?= $edit ? 'Edit #' . e($edit['id']) : 'Add ' . e(rtrim($title, 's')) ?></h2>
    <form class="stack-form" method="post" action="<?= e(url('/admin/' . $resource)) ?>">
        <?= csrf() ?>
        <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
        <div class="form-grid">
            <?php foreach ($definition['fields'] as $column => [$name, $type, $rule]): $value = $edit[$column] ?? ''; ?>
                <label>
                    <?= e($name) ?>
                    <?php if ($type === 'select'): ?>
                        <select name="<?= e($column) ?>" required>
                            <?php foreach ($rule as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $value === (string) $option ? 'selected' : '' ?>><?= e($column === 'is_active' ? ($option === '1' ? 'Active' : 'Inactive') : label($option)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($type === 'lookup'): ?>
                        <select name="<?= e($column) ?>" required>
                            <option value="">Choose <?= e(strtolower($name)) ?></option>
                            <?php foreach ($options[$column] as $option): ?>
                                <option value="<?= e($option['id']) ?>" <?= (string) $value === (string) $option['id'] ? 'selected' : '' ?>><?= e($option['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input name="<?= e($column) ?>" value="<?= e($value) ?>" type="<?= str_contains($type, 'date') ? 'date' : (in_array($type, ['money', 'integer', 'optional_integer'], true) ? 'number' : 'text') ?>" <?= str_starts_with($type, 'optional') ? '' : 'required' ?> <?= in_array($type, ['text', 'optional'], true) ? 'maxlength="' . e($rule) . '"' : '' ?> <?= in_array($type, ['money', 'integer', 'optional_integer'], true) ? 'min="' . ($type === 'money' ? '0' : '1') . '" max="' . e($rule) . '" step="' . ($type === 'money' ? '0.01' : '1') . '"' : '' ?>>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="button-row">
            <button class="btn btn-primary">Save Record</button>
            <?php if ($edit): ?>
                <a class="btn btn-outline-primary" href="<?= e(url('/admin/' . $resource)) ?>">Cancel Edit</a>
            <?php endif; ?>
        </div>
    </form>
</section>
