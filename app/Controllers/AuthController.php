<?php

namespace app\Controllers;

use app\Middleware\Auth;
use app\Models\DB;
use app\Services\AccountTokens;
use app\Services\FileStore;
use app\Services\RateLimit;
use app\Validators\Validate;

final class AuthController
{
    public function form(string $mode): void
    {
        view('auth/form', ['title' => label($mode), 'mode' => $mode]);
    }

    public function login(): void
    {
        RateLimit::check('login', 15);
        $email = Validate::email(input('email'));
        $user = DB::one('SELECT * FROM users WHERE email = ? AND status = "active"', [$email]);
        if (!$user || !password_verify(input('password'), $user['password'])) {
            throw new \DomainException('The email or password is incorrect.');
        }
        if (env('REQUIRE_EMAIL_VERIFICATION') === 'true' && !(FileStore::read('verified')[$user['id']] ?? false)) {
            throw new \DomainException('Verify your email before signing in. You can request a new link below.');
        }
        Auth::login($user);
        redirect('/dashboard');
    }

    public function register(): void
    {
        RateLimit::check('register', 8);
        $name = Validate::text(input('name'), 'Name', 100);
        $email = Validate::email(input('email'));
        $phone = Validate::text(input('phone'), 'Phone', 20, false);
        $password = Validate::password(input('password'));
        if ($password !== input('password_confirmation')) {
            throw new \DomainException('Passwords do not match.');
        }
        $id = DB::insert('users', ['role_id' => DB::value('SELECT id FROM roles WHERE name = "student"'),
            'name' => $name, 'email' => $email, 'phone' => $phone ?: null,
            'password' => password_hash($password, PASSWORD_DEFAULT)]);
        $user = DB::one('SELECT * FROM users WHERE id = ?', [$id]);
        if (env('REQUIRE_EMAIL_VERIFICATION') === 'true') {
            AccountTokens::issue($user, 'verify');
            flash('Account created. Check your email for the verification link.');
            redirect('/login');
        }
        Auth::login($user);
        flash('Welcome to HostelWash! Schedule your first pickup.');
        redirect('/orders/new');
    }

    public function forgot(): void
    {
        RateLimit::check('account-email', 5);
        $user = DB::one('SELECT * FROM users WHERE email = ? AND status = "active"', [Validate::email(input('email'))]);
        if ($user) {
            AccountTokens::issue($user, input('purpose') === 'verify' ? 'verify' : 'reset');
        }
        flash('If that account exists, an email with the next steps has been prepared.');
        redirect('/login');
    }

    public function reset(): void
    {
        RateLimit::check('reset');
        $password = Validate::password(input('password'));
        if ($password !== input('password_confirmation')) {
            throw new \DomainException('Passwords do not match.');
        }
        AccountTokens::consume(input('token'), 'reset', $password);
        flash('Your password has been changed. Sign in with your new password.');
        redirect('/login');
    }

    public function verify(): void
    {
        AccountTokens::consume(input('token'), 'verify');
        flash('Email verified. You can now sign in.');
        redirect('/login');
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        redirect('/login');
    }
}
