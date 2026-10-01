<section class="empty-state error-state">
    <span class="round-icon">!</span>
    <h1><?= e($title) ?></h1>
    <p><?= e($message) ?></p>
    <div class="button-row">
        <button class="btn btn-outline-primary go-back" type="button">Go back</button>
        <a class="btn btn-primary" href="<?= e(url($user ? '/dashboard' : '/')) ?>">Return home</a>
    </div>
</section>
