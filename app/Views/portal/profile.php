<div class="page-heading">
    <div>
        <h1>My Profile</h1>
        <p>Keep your contact details up to date.</p>
    </div>
</div>
<form class="panel stack-form reading-width" method="post" action="<?= e(url('/profile')) ?>">
    <?= csrf() ?><div class="profile-intro">
        <span class="avatar large"><?= e(mb_substr($user['name'], 0, 1)) ?></span>
        <div>
            <h2><?= e($user['name']) ?></h2>
            <span class="badge status-ready"><?= e(label($user['role'])) ?></span>
        </div>
    </div>
    <label>
        Full name
        <input name="name" maxlength="100" value="<?= e($user['name']) ?>" required autocomplete="name">
    </label>
    <label>
        Email
        <input value="<?= e($user['email']) ?>" type="email" disabled>
    </label>
    <label>
        Phone
        <input name="phone" maxlength="20" type="tel" value="<?= e($user['phone']) ?>" autocomplete="tel">
    </label>
    <hr>
    <h3>Change password <span class="muted">(optional)</span>
    </h3>
    <label>
        Current password
        <input name="current_password" type="password" autocomplete="current-password">
    </label>
    <label>
        New password
        <input name="password" type="password" minlength="10" maxlength="72" autocomplete="new-password">
    </label>
    <label>
        Confirm new password
        <input name="password_confirmation" type="password" autocomplete="new-password">
    </label>
    <button class="btn btn-primary">Save Changes</button>
</form>
