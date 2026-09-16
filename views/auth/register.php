<section class="section container page-narrow">
  <h1>Регистрация</h1>
  <form method="post" action="/register" class="form card-soft">
    <?= \App\Core\Csrf::field() ?>
    <label>Имя</label>
    <input type="text" name="name" value="<?= e(old('name')) ?>" required>
    <label>Email</label>
    <input type="email" name="email" value="<?= e(old('email')) ?>" required>
    <label>Telegram</label>
    <input type="text" name="telegram" value="<?= e(old('telegram')) ?>" placeholder="@username">
    <div class="row">
      <div>
        <label>Пароль</label>
        <input type="password" name="password" required minlength="8">
      </div>
      <div>
        <label>Повтор пароля</label>
        <input type="password" name="password2" required minlength="8">
      </div>
    </div>
    <p style="margin-top:1.2rem"><button class="btn btn-primary" type="submit">Создать аккаунт</button></p>
  </form>
  <p>Уже есть аккаунт? <a href="/login">Войти</a></p>
</section>
