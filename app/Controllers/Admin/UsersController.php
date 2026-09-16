<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AdminLog;
use App\Models\User;

final class UsersController
{
    public function index(): void
    {
        View::render('admin/users/index', [
            'title' => 'Пользователи',
            'users' => User::all(),
        ], 'admin');
    }

    public function update(string $id): void
    {
        Csrf::requireValid();
        $uid = (int) $id;
        if ($uid === (int) Auth::id()) {
            flash('error', 'Нельзя менять себя этим способом.');
            redirect('/admin/users');
        }
        $role = (string) ($_POST['role'] ?? 'user');
        if (!in_array($role, ['user', 'admin'], true)) {
            $role = 'user';
        }
        $banned = isset($_POST['is_banned']) ? 1 : 0;
        User::setRole($uid, $role);
        User::setBanned($uid, (bool) $banned);
        AdminLog::write((int) Auth::id(), 'user_update', client_ip(), "user={$uid};role={$role};banned={$banned}");
        flash('success', 'Пользователь обновлён.');
        redirect('/admin/users');
    }
}
