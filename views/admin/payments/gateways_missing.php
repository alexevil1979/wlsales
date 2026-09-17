<div class="panel-card">
  <h2 class="fi-section-title">Таблица payment_gateways ещё не создана</h2>
  <p class="fi-section-desc">На VPS выполните миграцию, затем обновите страницу:</p>
  <pre style="background:#f3f4f6;padding:1rem;border-radius:0.5rem;overflow:auto"><code>cd /ssd/www/wlsales
mysql -u root -p wlsales &lt; sql/003_payment_gateways.sql
sudo systemctl reload php8.2-fpm</code></pre>
</div>
