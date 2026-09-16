<section class="section container page-narrow">
  <p><a href="/account/tickets">← Тикеты</a></p>
  <h1>#<?= (int)$ticket['id'] ?> — <?= e($ticket['subject']) ?></h1>
  <p>Статус: <span class="pill"><?= e($ticket['status']) ?></span></p>
  <?php foreach ($messages as $m): ?>
    <div class="card-soft" style="margin-bottom:0.6rem">
      <div style="font-size:0.8rem;color:var(--muted);margin-bottom:0.4rem">
        <?= e($m['name'] ?: 'Пользователь') ?>
        <?= ($m['role'] ?? '') === 'admin' ? ' · поддержка' : '' ?>
        · <?= e($m['created_at']) ?>
      </div>
      <div><?= nl2br(e($m['body'])) ?></div>
    </div>
  <?php endforeach; ?>
  <?php if ($ticket['status'] !== 'closed'): ?>
    <form method="post" action="/account/tickets/<?= (int)$ticket['id'] ?>" class="form card-soft">
      <?= \App\Core\Csrf::field() ?>
      <label>Ответ</label>
      <textarea name="body" rows="3" required></textarea>
      <p style="margin-top:0.8rem"><button class="btn btn-primary" type="submit">Отправить</button></p>
    </form>
  <?php endif; ?>
</section>
