<section class="section container page-narrow">
  <h1>Смена пароля</h1>
  <form method="post" action="/account/password" class="form card-soft">
    <?= \App\Core\Csrf::field() ?>
    <label>Текущий пароль</label>
    <input type="password" name="current" required>
    <label>Новый пароль</label>
    <input type="password" name="password" required minlength="8">
    <label>Повтор</label>
    <input type="password" name="password2" required minlength="8">
    <p style="margin-top:1rem"><button class="btn btn-primary" type="submit">Сохранить</button></p>
  </form>
</section>
