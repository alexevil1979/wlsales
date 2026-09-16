<section class="section container page-narrow">
  <h1>Вход</h1>
  <form method="post" action="/login" class="form card-soft">
    <?= \App\Core\Csrf::field() ?>
    <label>Email</label>
    <input type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="username">
    <label>Пароль</label>
    <input type="password" name="password" required autocomplete="current-password">
    <p style="margin-top:1.2rem"><button class="btn btn-primary" type="submit">Войти</button></p>
  </form>
  <p>Нет аккаунта? <a href="/register">Регистрация</a></p>
</section>
