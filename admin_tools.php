<?php
define('SECRET_TOKEN', 'admin2026!tools');
if (!isset(\$_GET['token']) || \$_GET['token'] !== SECRET_TOKEN) {
    http_response_code(403); die('403 - Usa ?token=admin2026!tools');
}
preg_match('#/(sslip-[^/]+)/#', __DIR__, \$m);
\$currentSite = \$m[1] ?? '';
\$sites = [
    'sslip-bisceglie' => ['label'=>'Bisceglie','domain'=>'bisceglie.10-14-201-91.sslip.io','color'=>'#1565c0'],
    'sslip-canosa'    => ['label'=>'Canosa',   'domain'=>'canosa.10-14-201-91.sslip.io',   'color'=>'#4a148c'],
    'sslip-trani'     => ['label'=>'Trani',    'domain'=>'trani.10-14-201-91.sslip.io',    'color'=>'#1b5e20'],
];
\$tools = [
    ['file'=>'cron_manager.php','token'=>'cron2026!mgr','icon'=>'&#9201;','label'=>'Cron Manager','desc'=>'Variabili, esecuzione manuale, log'],
    ['file'=>'fix_setup.php',   'token'=>'bsc2026!fix', 'icon'=>'&#128295;','label'=>'Fix Setup',  'desc'=>'Fix DB e object-fit'],
];
?><!DOCTYPE html><html lang="it"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Tools</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#0d1b2e;color:#cde;min-height:100vh;padding:2rem 1rem}
.wrap{max-width:960px;margin:0 auto}
h1{font-size:1.5rem;font-weight:800;color:#42a5f5;margin-bottom:.3rem}
.sub{font-size:.8rem;opacity:.35;margin-bottom:2rem}
.sites{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.2rem}
.site-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:14px;overflow:hidden}
.site-hdr{padding:.8rem 1rem;font-weight:700;font-size:.92rem;display:flex;align-items:center;gap:.5rem}
.dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.cur{font-size:.58rem;padding:.1rem .35rem;background:rgba(255,255,255,.15);border-radius:4px;margin-left:.3rem;font-weight:400}
.tool-list{padding:.4rem .7rem .8rem}
.tlink{display:flex;align-items:flex-start;gap:.65rem;padding:.55rem .55rem;border-radius:9px;text-decoration:none;color:inherit;transition:background .18s;margin-bottom:.15rem}
.tlink:hover{background:rgba(255,255,255,.07)}
.tico{font-size:1rem;margin-top:.05rem;flex-shrink:0}
.tname{font-size:.82rem;font-weight:600;color:#e3f2fd}
.tdesc{font-size:.7rem;opacity:.4;margin-top:.08rem}
.div{height:1px;background:rgba(255,255,255,.06);margin:.35rem 0}
footer{margin-top:2.5rem;text-align:center;font-size:.7rem;opacity:.2}
</style></head><body><div class="wrap">
<h1>&#9881; Admin Tools</h1>
<div class="sub">Pannello strumenti amministratore &mdash; tutti i siti</div>
<div class="sites">
<?php foreach(\$sites as \$key=>\$site): ?>
<div class="site-card">
<div class="site-hdr" style="background:<?=\$site['color']?>22;border-bottom:1px solid <?=\$site['color']?>44">
<span class="dot" style="background:<?=\$site['color']?>"></span>
<?=htmlspecialchars(\$site['label'])?>
<?php if(\$key===\$currentSite):?><span class="cur">questo sito</span><?php endif;?>
</div>
<div class="tool-list">
<?php foreach(\$tools as \$i=>\$tool):
\$url='https://'.\$site['domain'].'/'.\$tool['file'].'?token='.\$tool['token'];
?>
<?php if(\$i>0):?><div class="div"></div><?php endif;?>
<a class="tlink" href="<?=htmlspecialchars(\$url)?>" target="_blank">
<span class="tico"><?=\$tool['icon']?></span>
<div><div class="tname"><?=htmlspecialchars(\$tool['label'])?></div>
<div class="tdesc"><?=htmlspecialchars(\$tool['desc'])?></div></div>
</a>
<?php endforeach;?>
</div></div>
<?php endforeach;?>
</div>
<footer>Token: admin2026!tools &mdash; <?=date('Y')?></footer>
</div></body></html>
