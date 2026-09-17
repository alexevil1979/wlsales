<div class="fi-header">
  <div>
    <h2 class="fi-header-heading" style="font-size:1.25rem;margin:0">Тикеты</h2>
    <p class="fi-header-sub">Обращения клиентов</p>
  </div>
</div>
<div class="table-wrap">
  <table class="data">
    <thead><tr><th>ID</th><th>Клиент</th><th>Тема</th><th>Статус</th><th>Дата</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($tickets as $t): ?>
      <tr>
        <td>#<?= (int)$t['id'] ?></td>
        <td><?= e($t['user_email']) ?></td>
        <td><?= e($t['subject']) ?></td>
        <td><span class="pill"><?= e($t['status']) ?></span></td>
        <td><?= e($t['created_at']) ?></td>
        <td><a class="btn btn-ghost btn-sm" href="/admin/tickets/<?= (int)$t['id'] ?>">Открыть</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
