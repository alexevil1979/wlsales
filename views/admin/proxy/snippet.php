<p><a href="/admin/proxy">← Белый вход</a></p>
<div class="panel-card">
  <h2 style="font-size:1.05rem;margin-top:0"><?= e($domain['domain']) ?></h2>
  <p>Origin: <?= e($domain['origin_host']) ?>:<?= (int)$domain['origin_port'] ?> · Node IP: <?= e($domain['node_ip'] ?: '—') ?></p>
  <p style="font-size:0.88rem">Чеклист: 1) DNS A = IP ноды 2) вставить сниппет в nginx 3) <code>certbot --nginx -d <?= e($domain['domain']) ?></code> 4) отметить ssl=issued и status=active</p>
  <pre style="background:#0f172a;color:#e2e8f0;padding:1rem;border-radius:0.75rem;overflow:auto;font-size:0.82rem;line-height:1.45"><?= e($snippet) ?></pre>
  <button class="btn btn-primary btn-sm" type="button" onclick="navigator.clipboard.writeText(document.querySelector('pre').innerText)">Копировать</button>
</div>
