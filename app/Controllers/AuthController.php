<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\RateLimiter;
use App\Core\Validator;
use App\Core\View;
use App\Models\User;

final class AuthController
{
    public function loginForm(): void
    {
        View::render('auth/login', [
            'title' => 'Вход — WL Sales',
        ]);
    }

    public function login(): void
    {
        Csrf::requireValid();
        $ip = client_ip();
        if (RateLimiter::tooManyLogins($ip)) {
            flash('error', 'Слишком много попыток. Подождите 15 минут.');
            redirect('/login');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        store_old(['email' => $email]);

        $v = new Validator($_POST);
        $v->required('email', 'Email')->email('email')->required('password', 'Пароль');
        if ($v->fails()) {
            flash('error', $v->firstError());
            redirect('/login');
        }

        RateLimiter::hitLogin($ip, $email);
        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            flash('error', 'Неверный email или пароль.');
            redirect('/login');
        }
        if ((int) $user['is_banned'] === 1) {
            flash('error', 'Аккаунт заблокирован.');
            redirect('/login');
        }

        clear_old();
        Auth::login($user);
        flash('success', 'С возвращением!');
        redirect(($user['role'] ?? '') === 'admin' ? '/admin' : '/account');
    }

    public function registerForm(): void
    {
        View::render('auth/register', [
            'title' => 'Регистрация — WL Sales',
        ]);
    }

    public function register(): void
    {
        Csrf::requireValid();
        $email = trim((string) ($_POST['email'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $telegram = trim((string) ($_POST['telegram'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $password2 = (string) ($_POST['password2'] ?? '');
        store_old(compact('email', 'name', 'telegram'));

        $v = new Validator($_POST);
        $v->required('email', 'Email')->email('email')
            ->required('name', 'Имя')->minLen('name', 2, 'Имя')
            ->required('password', 'Пароль')->minLen('password', 8, 'Пароль');
        if ($v->fails()) {
            flash('error', $v->firstError());
            redirect('/register');
        }
        if ($password !== $password2) {
            flash('error', 'Пароли не совпадают.');
            redirect('/register');
        }
        if (User::findByEmail($email)) {
            flash('error', 'Этот email уже зарегистрирован.');
            redirect('/register');
        }

        $id = User::create($email, $password, $name, $telegram);
        $user = User::findById($id);
        clear_old();
        if ($user) {
            Auth::login($user);
        }
        flash('success', 'Аккаунт создан. Можно выбирать сервер.');
        redirect('/catalog');
    }

    public function logout(): void
    {
        Csrf::requireValid();
        Auth::logout();
        flash('success', 'Вы вышли из аккаунта.');
        redirect('/');
    }
}
