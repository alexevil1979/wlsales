<?php
/** Admin proxy hub */
?>
<div class="fi-header">
  <div>
    <h2 class="fi-header-heading" style="font-size:1.25rem;margin:0">Белый вход</h2>
    <p class="fi-header-sub">Ноды, подписки, DNS-очередь</p>
  </div>
</div>
<div class="panel-stats">
  <div class="panel-stat"><div class="n"><?= count($nodes) ?></div><div class="l">Нод</div></div>
  <div class="panel-stat"><div class="n"><?= count($subscriptions) ?></div><div class="l">Подписок</div></div>
  <div class="panel-stat"><div class="n"><?= count($dnsQueue) ?></div><div class="l">Очередь DNS</div></div>
  <div class="panel-stat"><div class="n"><?= count($plans) ?></div><div class="l">Тарифов</div></div>
</div>

<div class="panel-card form" style="margin-bottom:1rem">
  <h2 class="fi-section-title">Новая нода</h2>
  <form method="post" action="/admin/proxy/nodes">
    <?= \App\Core\Csrf::field() ?>
    <div class="row">
      <div>
        <label>Из инвентаря (delivered)</label>
        <select name="server_id">
          <option value="">— вручную —</option>
          <?php foreach ($servers as $srv): ?>
            <option value="<?= (int)$srv['id'] ?>">#<?= (int)$srv['id'] ?> <?= e($srv['ip']) ?> <?= e($srv['hostname']) ?> (<?= e($srv['status']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>Public IP</label><input name="public_ip" placeholder="если не из сервера"></div>
    </div>
    <div class="row">
      <div><label>Hostname</label><input name="hostname"></div>
      <div><label>Лимит доменов</label><input name="domains_cap" type="number" value="50"></div>
    </div>
    <div class="row">
      <div><label>Локация</label><input name="location"></div>
      <div><label>Вендор</label><input name="vendor"></div>
    </div>
    <button class="btn btn-primary btn-sm" type="submit">Создать ноду</button>
  </form>
</div>

<div class="panel-card" style="margin-bottom:1rem">
  <h2 class="fi-section-title">Ноды</h2>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>ID</th><th>IP</th><th>Исп./лимит</th><th>Статус</th><th>Сервер</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($nodes as $n): ?>
        <tr>
          <td>#<?= (int)$n['id'] ?></td>
          <td><code><?= e($n['public_ip']) ?></code><br><?= e($n['hostname']) ?></td>
          <td><?= (int)$n['domains_used'] ?> / <?= (int)$n['domains_cap'] ?></td>
          <td><?= e($n['status']) ?></td>
          <td><?= $n['server_id'] ? '#' . (int)$n['server_id'] : '—' ?></td>
          <td>
            <details>
              <summary>Изменить</summary>
              <form method="post" action="/admin/proxy/nodes/<?= (int)$n['id'] ?>" class="form" style="min-width:240px">
                <?= \App\Core\Csrf::field() ?>
                <label>IP</label><input name="public_ip" value="<?= e($n['public_ip']) ?>">
                <label>Hostname</label><input name="hostname" value="<?= e($n['hostname']) ?>">
                <label>Cap</label><input name="domains_cap" type="number" value="<?= (int)$n['domains_cap'] ?>">
                <label>Статус</label>
                <select name="status">
                  <?php foreach (['active','full','maintenance','dead'] as $st): ?>
                    <option value="<?= $st ?>" <?= $n['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="hidden" name="server_id" value="<?= (int)($n['server_id'] ?? 0) ?>">
                <input type="hidden" name="location" value="<?= e($n['location']) ?>">
                <input type="hidden" name="vendor" value="<?= e($n['vendor']) ?>">
                <label>Заметка</label><textarea name="note_admin" rows="2"><?= e((string)$n['note_admin']) ?></textarea>
                <button class="btn btn-primary btn-sm" type="submit">OK</button>
              </form>
              <form method="post" action="/admin/proxy/nodes/<?= (int)$n['id'] ?>/dead" onsubmit="return confirm('Пометить dead и затронуть домены?')" style="margin-top:0.4rem">
                <?= \App\Core\Csrf::field() ?>
                <button class="btn btn-danger btn-sm" type="submit">Нода умерла</button>
              </form>
            </details>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="panel-card" style="margin-bottom:1rem">
  <h2 class="fi-section-title">Очередь DNS</h2>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Домен</th><th>Ожидаемый IP</th><th>Клиент</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($dnsQueue as $d): ?>
        <tr>
          <td><?= e($d['domain']) ?></td>
          <td><?= e($d['node_ip'] ?: 'нет ноды') ?></td>
          <td><?= e($d['user_email']) ?></td>
          <td>
            <form method="post" action="/admin/proxy/domains/<?= (int)$d['id'] ?>/check-dns" style="display:inline">
              <?= \App\Core\Csrf::field() ?>
              <button class="btn btn-primary btn-sm" type="submit">Проверить DNS</button>
            </form>
            <a class="btn btn-ghost btn-sm" href="/admin/proxy/domains/<?= (int)$d['id'] ?>/snippet">Сниппет</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$dnsQueue): ?><tr><td colspan="4">Очередь пуста.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="panel-card" style="margin-bottom:1rem">
  <h2 class="fi-section-title">Подписки</h2>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>ID</th><th>Клиент</th><th>Тариф</th><th>Нода</th><th>Статус</th><th>До</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($subscriptions as $s): ?>
        <tr>
          <td>#<?= (int)$s['id'] ?></td>
          <td><?= e($s['user_email']) ?></td>
          <td><?= e($s['plan_title']) ?><?= (int)$s['dedicated_ip'] ? ' ★' : '' ?></td>
          <td><?= e($s['node_ip'] ?: '—') ?></td>
          <td><?= e($s['status']) ?></td>
          <td><?= e($s['period_end'] ?: '—') ?></td>
          <td>
            <form method="post" action="/admin/proxy/subscriptions/<?= (int)$s['id'] ?>/assign" style="display:flex;gap:0.3rem;flex-wrap:wrap">
              <?= \App\Core\Csrf::field() ?>
              <select name="node_id" required>
                <option value="">нода…</option>
                <?php foreach ($nodes as $n): ?>
                  <option value="<?= (int)$n['id'] ?>">#<?= (int)$n['id'] ?> <?= e($n['public_ip']) ?> (<?= (int)$n['domains_used'] ?>/<?= (int)$n['domains_cap'] ?>)</option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-ghost btn-sm" type="submit">Назначить</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="panel-card">
  <h2 class="fi-section-title">Все домены</h2>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Домен</th><th>Origin</th><th>Статус</th><th>SSL</th><th></th></tr></thead>
      <tbody>
      <?php foreach (\App\Models\ProxyDomain::allAdmin() as $d): ?>
        <tr id="domain-<?= (int)$d['id'] ?>">
          <td><?= e($d['domain']) ?><br><small><?= e($d['user_email']) ?></small></td>
          <td><?= e($d['origin_host']) ?>:<?= (int)$d['origin_port'] ?></td>
          <td><?= e($d['status']) ?></td>
          <td><?= e($d['ssl_status']) ?></td>
          <td>
            <a href="/admin/proxy/domains/<?= (int)$d['id'] ?>/snippet">Сниппет</a>
            <details>
              <summary>Статусы</summary>
              <form method="post" action="/admin/proxy/domains/<?= (int)$d['id'] ?>" class="form">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="origin_host" value="<?= e($d['origin_host']) ?>">
                <input type="hidden" name="origin_port" value="<?= (int)$d['origin_port'] ?>">
                <input type="hidden" name="origin_sni" value="<?= e((string)$d['origin_sni']) ?>">
                <?php if ((int)$d['origin_https']): ?><input type="hidden" name="origin_https" value="1"><?php endif; ?>
                <label>status</label>
                <select name="status">
                  <?php foreach (['pending_dns','active','error','disabled'] as $st): ?>
                    <option value="<?= $st ?>" <?= $d['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                  <?php endforeach; ?>
                </select>
                <label>ssl</label>
                <select name="ssl_status">
                  <?php foreach (['pending','issued','error'] as $st): ?>
                    <option value="<?= $st ?>" <?= $d['ssl_status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                  <?php endforeach; ?>
                </select>
                <label>note</label>
                <textarea name="note_admin" rows="2"><?= e((string)$d['note_admin']) ?></textarea>
                <button class="btn btn-primary btn-sm" type="submit">Сохранить</button>
              </form>
              <form method="post" action="/admin/proxy/domains/<?= (int)$d['id'] ?>/check-dns" style="margin-top:0.3rem">
                <?= \App\Core\Csrf::field() ?>
                <button class="btn btn-ghost btn-sm" type="submit">DNS</button>
              </form>
            </details>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
