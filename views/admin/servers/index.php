<div class="card-soft form" style="margin-bottom:1.2rem">
  <h2 style="font-size:1.05rem">Добавить сервер</h2>
  <form method="post" action="/admin/servers">
    <?= \App\Core\Csrf::field() ?>
    <div class="row">
      <div>
        <label>Товар</label>
        <select name="product_id">
          <option value="">—</option>
          <?php foreach ($products as $p): ?>
            <option value="<?= (int)$p['id'] ?>"><?= e($p['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>IP</label><input name="ip" required></div>
    </div>
    <div class="row">
      <div><label>Hostname</label><input name="hostname"></div>
      <div><label>Panel URL</label><input name="panel_url"></div>
    </div>
    <div class="row">
      <div><label>Логин (подсказка клиенту)</label><input name="login_hint" placeholder="root"></div>
      <div><label>Секрет / пароль (шифруется)</label><input name="secret" type="password" autocomplete="new-password"></div>
    </div>
    <label>Заметка админа (клиенту не видна)</label>
    <textarea name="note_admin" rows="2"></textarea>
    <input type="hidden" name="status" value="free">
    <p style="margin-top:0.8rem"><button class="btn btn-primary btn-sm" type="submit">Добавить</button></p>
  </form>
</div>

<div class="table-wrap card-soft">
  <table class="data">
    <thead><tr><th>ID</th><th>IP / Host</th><th>Товар</th><th>Клиент</th><th>Статус</th><th>Заметка</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($servers as $s): ?>
      <tr>
        <td>#<?= (int)$s['id'] ?></td>
        <td><code><?= e($s['ip']) ?></code><br><?= e($s['hostname']) ?></td>
        <td><?= e($s['product_title'] ?? '—') ?></td>
        <td><?= e($s['user_email'] ?? '—') ?></td>
        <td><?= e($s['status']) ?></td>
        <td style="max-width:160px"><?= e(mb_substr((string)$s['note_admin'], 0, 80)) ?></td>
        <td>
          <details>
            <summary>Изменить</summary>
            <form method="post" action="/admin/servers/<?= (int)$s['id'] ?>" class="form" style="min-width:240px">
              <?= \App\Core\Csrf::field() ?>
              <label>IP</label><input name="ip" value="<?= e($s['ip']) ?>">
              <label>Hostname</label><input name="hostname" value="<?= e($s['hostname']) ?>">
              <label>Panel</label><input name="panel_url" value="<?= e($s['panel_url']) ?>">
              <label>Логин</label><input name="login_hint" value="<?= e($s['login_hint']) ?>">
              <label>Новый секрет (пусто = не менять)</label><input name="secret" type="password">
              <label>Товар</label>
              <select name="product_id">
                <option value="">—</option>
                <?php foreach ($products as $p): ?>
                  <option value="<?= (int)$p['id'] ?>" <?= (int)($s['product_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['title']) ?></option>
                <?php endforeach; ?>
              </select>
              <label>Статус</label>
              <select name="status">
                <?php foreach (['free','reserved','assigned','offline'] as $st): ?>
                  <option value="<?= $st ?>" <?= $s['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
              </select>
              <label>Заметка</label>
              <textarea name="note_admin" rows="2"><?= e((string)$s['note_admin']) ?></textarea>
              <button class="btn btn-primary btn-sm" type="submit">Сохранить</button>
            </form>
            <form method="post" action="/admin/servers/<?= (int)$s['id'] ?>/delete" onsubmit="return confirm('Удалить?')" style="margin-top:0.4rem">
              <?= \App\Core\Csrf::field() ?>
              <button class="btn btn-danger btn-sm" type="submit">Удалить</button>
            </form>
          </details>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
