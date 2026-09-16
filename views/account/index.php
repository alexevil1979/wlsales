<section class="section container">
  <h1>Кабинет</h1>
  <div class="grid-2" style="align-items:start">
    <div class="card-soft form">
      <h2 style="font-size:1.1rem">Профиль</h2>
      <form method="post" action="/account/profile">
        <?= \App\Core\Csrf::field() ?>
        <label>Имя</label>
        <input type="text" name="name" value="<?= e($user['name']) ?>" required>
        <label>Telegram</label>
        <input type="text" name="telegram" value="<?= e($user['telegram']) ?>">
        <label>Email</label>
        <input type="text" value="<?= e($user['email']) ?>" disabled>
        <p style="margin-top:1rem">
          <button class="btn btn-primary btn-sm" type="submit">Сохранить</button>
          <a class="btn btn-ghost btn-sm" href="/account/password">Сменить пароль</a>
        </p>
      </form>
    </div>
    <div>
      <div class="card-soft" style="margin-bottom:1rem">
        <h2 style="font-size:1.1rem">Быстрые ссылки</h2>
        <p><a href="/account/orders">Мои заказы</a> · <a href="/account/servers">Мои серверы</a> · <a href="/account/tickets">Тикеты</a></p>
        <p><a class="btn btn-primary btn-sm" href="/catalog">Выбрать сервер</a></p>
      </div>
      <div class="card-soft">
        <h2 style="font-size:1.1rem">Последние заказы</h2>
        <?php if (!$orders): ?><p>Пока пусто.</p><?php endif; ?>
        <ul style="margin:0;padding-left:1.1rem">
          <?php foreach (array_slice($orders, 0, 5) as $o): ?>
            <li><a href="/account/orders/<?= (int)$o['id'] ?>">#<?= (int)$o['id'] ?></a> — <?= e($o['product_title']) ?> · <?= e(order_status_label($o['status'])) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</section>
