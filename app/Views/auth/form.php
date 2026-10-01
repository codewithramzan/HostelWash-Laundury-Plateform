<div class="auth-layout">
    <div class="auth-story">
        <span class="eyebrow">A FRESHER EVERYDAY</span>
        <h1>Laundry off your list.<br>Life on your terms.</h1>
        <p>Your hostel laundry, taken care of from pickup to delivery.</p>
        <div class="auth-emblem">
            <img src="<?= e(url('/assets/images/favicon.svg')) ?>" alt="">
            <strong>HostelWash</strong>
            <small>Fresh Clothes. Brighter Days.</small>
        </div>
    </div>
    <section class="panel auth-card">
        <h2><?= e(match ($mode) {'login' => 'Welcome back', 'register' => 'Create your account', 'forgot' => 'Recover your account', 'reset' => 'Choose a new password', default => 'Verify your email'}) ?></h2>
        <p class="muted"><?= $mode === 'register' ? 'A fresh start is just a few details away.' : 'Let’s get you ready for a brighter day.' ?></p>
        <form method="post" action="<?= e(url('/' . $mode)) ?>" class="stack-form">
            <?= csrf() ?>
            <?php if ($mode === 'register'): ?>
                <label>
                    Full name
                    <input name="name" autocomplete="name" maxlength="100" required>
                </label>
                <label>
                    Phone <span class="muted">(optional)</span>
                    <input name="phone" type="tel" autocomplete="tel" maxlength="20">
                </label>
            <?php endif; ?>
            <?php if (in_array($mode, ['login', 'register', 'forgot'], true)): ?>
                <label>
                    Email address
                    <input name="email" type="email" autocomplete="email" maxlength="100" required>
                </label>
            <?php endif; ?>
            <?php if (in_array($mode, ['login', 'register', 'reset'], true)): ?>
                <label>
                    Password
                    <input name="password" type="password" autocomplete="<?= $mode === 'login' ? 'current-password' : 'new-password' ?>" <?= $mode !== 'login' ? 'minlength="10" maxlength="72"' : '' ?> required>
                </label>
            <?php endif; ?>
            <?php if (in_array($mode, ['register', 'reset'], true)): ?>
                <label>
                    Confirm password
                    <input name="password_confirmation" type="password" autocomplete="new-password" minlength="10" maxlength="72" required>
                </label>
                <small class="muted">Use at least 10 characters.</small>
            <?php endif; ?>
            <?php if (in_array($mode, ['reset', 'verify'], true)): ?>
                <input type="hidden" name="token" value="<?= e($_GET['token'] ?? '') ?>">
            <?php endif; ?>
            <?php if ($mode === 'forgot'): ?>
                <label>
                    Request
                    <select name="purpose">
                        <option value="reset">Password reset</option>
                        <option value="verify">New email verification link</option>
                    </select>
                </label>
            <?php endif; ?>
            <?php if ($mode === 'register'): ?>
                <small class="muted">By signing up, you agree to our <a href="<?= e(url('/terms')) ?>">Terms</a> and <a href="<?= e(url('/privacy')) ?>">Privacy Policy</a>
                    .</small>
            <?php endif; ?>
            <button class="btn btn-primary w-100"><?= e(match ($mode) {'login' => 'Sign In', 'register' => 'Create Account', 'forgot' => 'Send Link', 'reset' => 'Reset Password', default => 'Verify Email'}) ?></button>
        </form>
        <?php if ($mode === 'login'): ?>
            <a class="text-link" href="<?= e(url('/forgot')) ?>">Forgot password?</a>
            <p class="auth-bottom">New to HostelWash? <a href="<?= e(url('/register')) ?>">Create an account</a>
            </p>
        <?php else: ?>
            <a class="text-link" href="<?= e(url('/login')) ?>">Back to sign in</a>
        <?php endif; ?>
    </section>
</div>
