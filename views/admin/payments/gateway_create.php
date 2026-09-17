<div class="fi-header">
  <div>
    <h2 class="fi-header-heading">Создать шлюз</h2>
    <p class="fi-header-sub">Новая запись в payment_gateways</p>
  </div>
  <div class="fi-header-actions">
    <a class="btn btn-ghost" href="/admin/payments/gateways">К списку</a>
  </div>
</div>

<form method="post" action="/admin/payments/gateways/create" class="form panel-card" style="max-width:40rem">
  <?= \App\Core\Csrf::field() ?>
  <label>Код</label>
  <input name="code" required placeholder="например freekassa_sbp" pattern="[a-z0-9_\-]+" autocomplete="off">
  <label>Имя</label>
  <input name="name" required placeholder="Отображаемое имя">
  <label><input type="checkbox" name="enabled" value="1"> Включён</label>
  <label><input type="checkbox" name="test_mode" value="1" checked> Тестовый режим</label>
  <p style="margin-top:1.25rem"><button class="btn btn-primary" type="submit">Создать</button></p>
</form>
