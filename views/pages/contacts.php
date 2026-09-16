<section class="section container page-narrow">
  <h1>Контакты</h1>
  <div class="card-soft">
    <p>Telegram: <?php if ($telegram): ?><a href="<?= e($telegram) ?>" target="_blank" rel="noopener"><?= e($telegram) ?></a><?php else: ?>уточняется<?php endif; ?></p>
    <p>Email: <?php if ($email): ?><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php else: ?>уточняется<?php endif; ?></p>
    <p style="margin-top:1rem">По вопросам заказов и замены IP удобнее тикет в <a href="/account/tickets">кабинете</a>.</p>
  </div>
</section>
