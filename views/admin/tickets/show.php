<p><a href="/admin/tickets">← Тикеты</a></p>
<div class="panel-card" style="margin-bottom:1rem">
  <div style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;justify-content:space-between">
    <div>
      <strong>#<?= (int)$ticket['id'] ?></strong> — <?= e($ticket['subject']) ?>
      <div style="font-size:0.88rem;color:#6b7280;margin-top:0.25rem">
        <?= e($ticket['user_email']) ?>
        <?php if (!empty($ticket['user_name'])): ?> · <?= e($ticket['user_name']) ?><?php endif; ?>
        <?php if (!empty($ticket['order_id'])): ?> · заказ <a href="/admin/orders/<?= (int)$ticket['order_id'] ?>">#<?= (int)$ticket['order_id'] ?></a><?php endif; ?>
      </div>
    </div>
    <span class="pill"><?= e($ticket['status']) ?></span>
  </div>
</div>

<?php foreach ($messages as $m): ?>
  <?php $isAdmin = ($m['role'] ?? '') === 'admin'; ?>
  <div class="card-soft" style="margin-bottom:0.5rem;<?= $isAdmin ? 'border-color:#fcd34d;background:#fffbeb' : '' ?>">
    <div style="font-size:0.8rem;color:var(--muted)">
      <?= e($m['name'] ?: 'Пользователь') ?>
      <?= $isAdmin ? ' · поддержка' : ' · клиент' ?>
      · <?= e($m['created_at']) ?>
    </div>
    <div style="margin-top:0.35rem"><?= nl2br(e($m['body'])) ?></div>
  </div>
<?php endforeach; ?>

<form method="post" action="/admin/tickets/<?= (int)$ticket['id'] ?>" class="form panel-card">
  <?= \App\Core\Csrf::field() ?>
  <label>Ответ клиенту (уйдёт письмо на email)</label>
  <textarea name="body" rows="4" placeholder="Текст ответа…"></textarea>
  <label>Статус</label>
  <select name="status">
    <?php
      $labels = ['open' => 'open — ждёт ответа', 'answered' => 'answered — ответили', 'closed' => 'closed — закрыт'];
      foreach ($labels as $st => $label):
    ?>
      <option value="<?= $st ?>" <?= $ticket['status'] === $st ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <p style="margin-top:0.8rem"><button class="btn btn-primary" type="submit">Отправить ответ</button></p>
</form>
