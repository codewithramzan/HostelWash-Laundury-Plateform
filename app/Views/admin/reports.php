<div class="page-heading">
    <div>
        <h1>Business Reports</h1>
        <p>Cash revenue, expenses and orders for your selected period.</p>
    </div>
    <button type="button" class="btn btn-outline-primary print-page">Print / Save PDF</button>
</div>
<form method="get" class="filter-bar">
    <label>
        From
        <input name="from" type="date" value="<?= e($from) ?>" required>
    </label>
    <label>
        To
        <input name="to" type="date" value="<?= e($to) ?>" required>
    </label>
    <button class="btn btn-primary">Apply</button>
    <a class="btn btn-outline-primary" href="?<?= e(http_build_query(['from' => $from, 'to' => $to, 'export' => 'csv'])) ?>">Export CSV</a>
</form>
<div class="stat-grid">
    <article class="stat-card">
        <div>
            <small>Cash Revenue</small>
            <strong><?= e(money($revenue)) ?></strong>
        </div>
    </article>
    <article class="stat-card">
        <div>
            <small>Expenses</small>
            <strong><?= e(money($expenses)) ?></strong>
        </div>
    </article>
    <article class="stat-card">
        <div>
            <small>Cash Profit</small>
            <strong><?= e(money($revenue - $expenses)) ?></strong>
        </div>
    </article>
    <article class="stat-card">
        <div>
            <small>Orders Placed</small>
            <strong><?= count($rows) ?></strong>
        </div>
    </article>
</div>
<section class="panel mb-4">
    <h2>Orders by Hostel</h2>
    <div class="chart-box">
        <canvas role="img" aria-label="Orders by hostel" data-chart="bar" data-points="<?= e(json_encode($byHostel)) ?>">
        </canvas>
    </div>
</section>
<section class="panel">
    <h2>Orders in Period</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Student</th>
                    <th>Hostel</th>
                    <th>Status</th>
                    <th>Actual kg</th>
                    <th>Total</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($row as $cell): ?>
                            <td><?= e($cell ?? '—') ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?>
        <div class="empty-state">
            No orders in this date range.</div>
    <?php endif; ?>
</section>
