<section class="section container">
  <h1>Тикеты</h1>
  <div class="grid-2" style="align-items:start">
    <div class="card-soft form">
      <h2 style="font-size:1.1rem">Новый тикет</h2>
      <form method="post" action="/account/tickets">
        <?= \App\Core\Csrf::field() ?>
        <label>Тема</label>
        <select name="subject" required>
          <option value="IP не проходит проверку">IP не проходит проверку</option>
          <option value="Нужна смена IP">Нужна смена IP</option>
          <option value="Продление аренды">Продление аренды</option>
          <option value="Другое">Другое</option>
        </select>
        <label>Заказ (опционально)</label>
        <select name="order_id">
          <option value="">—</option>
          <?php foreach ($orders as $o): ?>
            <option value="<?= (int)$o['id'] ?>">#<?= (int)$o['id'] ?> — <?= e($o['product_title']) ?></option>
          <?php endforeach; ?>
        </select>
        <label>Сообщение</label>
        <textarea name="body" rows="4" required></textarea>
        <p style="margin-top:1rem"><button class="btn btn-primary" type="submit">Отправить</button></p>
      </form>
    </div>
    <div class="card-soft table-wrap">
      <table class="data">
        <thead><tr><th>ID</th><th>Тема</th><th>Статус</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
          <tr>
            <td>#<?= (int)$t['id'] ?></td>
            <td><?= e($t['subject']) ?></td>
            <td><?= e($t['status']) ?></td>
            <td><a href="/account/tickets/<?= (int)$t['id'] ?>">Открыть</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$tickets): ?><tr><td colspan="4">Тикетов пока нет.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
