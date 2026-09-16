<div class="table-wrap card-soft">
  <table class="data">
    <thead><tr><th>ID</th><th>Клиент</th><th>Тема</th><th>Статус</th><th>Дата</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($tickets as $t): ?>
      <tr>
        <td>#<?= (int)$t['id'] ?></td>
        <td><?= e($t['user_email']) ?></td>
        <td><?= e($t['subject']) ?></td>
        <td><?= e($t['status']) ?></td>
        <td><?= e($t['created_at']) ?></td>
        <td><a href="/admin/tickets/<?= (int)$t['id'] ?>">Открыть</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
