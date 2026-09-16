<p><a href="/admin/tickets">← Тикеты</a></p>
<p><?= e($ticket['user_email']) ?> · <?= e($ticket['subject']) ?> · <?= e($ticket['status']) ?></p>
<?php foreach ($messages as $m): ?>
  <div class="card-soft" style="margin-bottom:0.5rem">
    <div style="font-size:0.8rem;color:var(--muted)"><?= e($m['name']) ?> (<?= e($m['role']) ?>) · <?= e($m['created_at']) ?></div>
    <div><?= nl2br(e($m['body'])) ?></div>
  </div>
<?php endforeach; ?>
<form method="post" action="/admin/tickets/<?= (int)$ticket['id'] ?>" class="form card-soft">
  <?= \App\Core\Csrf::field() ?>
  <label>Ответ</label>
  <textarea name="body" rows="4"></textarea>
  <label>Статус</label>
  <select name="status">
    <?php foreach (['open','answered','closed'] as $st): ?>
      <option value="<?= $st ?>" <?= $ticket['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
    <?php endforeach; ?>
  </select>
  <p style="margin-top:0.8rem"><button class="btn btn-primary" type="submit">Сохранить</button></p>
</form>
