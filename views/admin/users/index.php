<div class="table-wrap card-soft">
  <table class="data">
    <thead><tr><th>ID</th><th>Email</th><th>Имя</th><th>Telegram</th><th>Роль</th><th>Бан</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= (int)$u['id'] ?></td>
        <td><?= e($u['email']) ?></td>
        <td><?= e($u['name']) ?></td>
        <td><?= e($u['telegram']) ?></td>
        <td colspan="3">
          <form method="post" action="/admin/users/<?= (int)$u['id'] ?>" style="display:flex;gap:0.4rem;flex-wrap:wrap;align-items:center">
            <?= \App\Core\Csrf::field() ?>
            <select name="role">
              <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>user</option>
              <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
            </select>
            <label style="display:flex;gap:0.3rem;align-items:center;margin:0;color:var(--text)">
              <input type="checkbox" name="is_banned" value="1" <?= (int)$u['is_banned'] ? 'checked' : '' ?>> бан
            </label>
            <button class="btn btn-ghost btn-sm" type="submit">OK</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
