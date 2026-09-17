<div class="fi-header">
  <div>
    <h2 class="fi-header-heading" style="font-size:1.25rem;margin:0">Пользователи</h2>
    <p class="fi-header-sub">Роли и блокировки</p>
  </div>
</div>
<div class="table-wrap">
  <table class="data">
    <thead><tr><th>ID</th><th>Email</th><th>Имя</th><th>Telegram</th><th>Роль / бан</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= (int)$u['id'] ?></td>
        <td><?= e($u['email']) ?></td>
        <td><?= e($u['name']) ?></td>
        <td><?= e($u['telegram']) ?></td>
        <td>
          <form method="post" action="/admin/users/<?= (int)$u['id'] ?>" style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center">
            <?= \App\Core\Csrf::field() ?>
            <select name="role" style="width:auto;min-width:6rem">
              <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>user</option>
              <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
            </select>
            <label style="display:flex;gap:0.35rem;align-items:center;margin:0">
              <input type="checkbox" name="is_banned" value="1" <?= (int)$u['is_banned'] ? 'checked' : '' ?>> бан
            </label>
            <button class="btn btn-primary btn-sm" type="submit">Сохранить</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
