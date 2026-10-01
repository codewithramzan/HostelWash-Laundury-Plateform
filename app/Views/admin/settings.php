<div class="page-heading">
    <div>
        <h1><?= e($title) ?></h1>
        <p><?= $section === 'content' ? 'Update the words your customers see.' : 'Configure contact details and pickup slots.' ?></p>
    </div>
</div>
<form method="post" action="<?= e(url('/admin/settings')) ?>" class="panel stack-form reading-width">
    <?= csrf() ?>
    <input type="hidden" name="section" value="<?= e($section) ?>">
    <?php $keys = $section === 'content' ? ['headline', 'intro', 'about', 'terms', 'privacy'] : ['contact_email', 'contact_phone', 'pickup_slots']; ?>
    <?php foreach ($keys as $key): ?>
        <label>
            <?= e(label($key)) ?>
            <?php if (in_array($key, ['about', 'terms', 'privacy'], true)): ?>
                <textarea name="<?= e($key) ?>" rows="7" maxlength="10000" required><?= e($settings[$key]) ?></textarea>
            <?php else: ?>
                <input name="<?= e($key) ?>" value="<?= e($settings[$key]) ?>" maxlength="500" <?= !str_starts_with($key, 'contact_') ? 'required' : '' ?>>
            <?php endif; ?>
        </label>
    <?php endforeach; ?>
    <?php if ($section === 'settings'): ?>
        <p class="muted">Separate pickup slots with commas. Changes apply to new bookings.</p>
    <?php endif; ?>
    <button class="btn btn-primary">Save Changes</button>
</form>
