<?php
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
include 'connect.php';
function getClientIP2(){if(!empty($_SERVER["HTTP_X_REAL_IP"])){return $_SERVER["HTTP_X_REAL_IP"];}elseif(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){return explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"])[0];}return $_SERVER["REMOTE_ADDR"];}
$ipclient = getClientIP2();
$cfgDef=['sfondo_tipo'=>'gradiente','sfondo_colore1'=>'#0d1b3e','sfondo_colore2'=>'#1a3a6e','sfondo_immagine'=>'','logo_immagine'=>'','news_testo'=>'','news_velocita'=>60,'news_attivo'=>1,'colore_primario'=>'#42a5f5','colore_testo'=>'#1565c0','colore_riga_pari'=>'#0d47a1','colore_riga_dispari'=>'#0a1628','animazione'=>'flip','mostra_multimedia'=>1,'titolo_struttura'=>'OSPEDALE','multimedia_durata'=>5,'multimedia_immagine_servizio'=>''];
$cfg=$cfgDef;
$rsCfg=mysqli_query($conn,"SELECT * FROM config_monitor2 WHERE id=1 LIMIT 1");
if($rsCfg&&$row=mysqli_fetch_assoc($rsCfg)){foreach($cfgDef as $k=>$v){$cfg[$k]=(isset($row[$k])&&$row[$k]!==null&&$row[$k]!=='')? $row[$k]:$v;}}
$turni=[];
$rsTurni=mysqli_query($conn,"SELECT * FROM turni WHERE stato='A' ORDER BY priorita");
while($t=mysqli_fetch_assoc($rsTurni)){
  $id=intval($t['ID_turno']);
  $rsN=mysqli_query($conn,"SELECT numero,id_postazione FROM contatori WHERE tipo='NUMERO' AND id_turno=$id ORDER BY ID_contatore DESC LIMIT 1");
  $numero=0;$postazione='-';
  if($rowN=mysqli_fetch_assoc($rsN)){$numero=intval($rowN['numero']);$pid=intval($rowN['id_postazione']);if($pid>0){$rsPo=mysqli_query($conn,"SELECT postazione FROM postazioni WHERE ID_postazione=$pid LIMIT 1");if($rowPo=mysqli_fetch_assoc($rsPo))$postazione=htmlspecialchars($rowPo['postazione']);}}
  $rsCoda=mysqli_query($conn,"SELECT numero FROM contatori WHERE tipo='CODA' AND id_turno=$id ORDER BY ID_contatore DESC LIMIT 1");
  $lastCodaNum=0;if($rowC=mysqli_fetch_assoc($rsCoda))$lastCodaNum=intval($rowC['numero']);
  $inCoda=max(0,$lastCodaNum-$numero);
  $turni[]=['id'=>$id,'lettera'=>htmlspecialchars($t['turno']),'nome'=>htmlspecialchars($t['desstato']),'numero'=>$numero,'postazione'=>$postazione,'in_coda'=>$inCoda,'coda_num'=>$lastCodaNum];
}
mysqli_close($conn);
if($cfg['sfondo_tipo']==='immagine'&&$cfg['sfondo_immagine']){$ext=pathinfo($cfg['sfondo_immagine'],PATHINFO_EXTENSION);$bgCSS="background:url('immagini/sfondo_monitor2.$ext') center/cover no-repeat;";}
elseif($cfg['sfondo_tipo']==='colore'){$bgCSS='background:'.htmlspecialchars($cfg['sfondo_colore1']).';';}
else{$bgCSS='background:linear-gradient(160deg,'.htmlspecialchars($cfg['sfondo_colore1']).' 0%,'.htmlspecialchars($cfg['sfondo_colore2']).' 100%);';}
$logoExt=$cfg['logo_immagine']?pathinfo($cfg['logo_immagine'],PATHINFO_EXTENSION):'';
$hasLogo=$logoExt&&file_exists(__DIR__.'/immagini/logo_monitor2.'.$logoExt);
$ttsCfgFile=__DIR__.'/totem_ui_config.json';
$ttsCfg=file_exists($ttsCfgFile)?(json_decode(file_get_contents($ttsCfgFile),true)?:[]):[];
$ttsVoceInit=(isset($ttsCfg['tts_voce'])&&$ttsCfg['tts_voce']!=='')?$ttsCfg['tts_voce']:'ElsaNeural';
$ttsVelInit=(isset($ttsCfg['tts_velocita'])&&intval($ttsCfg['tts_velocita'])>0)?intval($ttsCfg['tts_velocita']):135;
$ttsRevInit=file_exists($ttsCfgFile)?intval(@filemtime($ttsCfgFile)):0;
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Monitor Coda</title>
<script src="jquery-3.6.0.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --accento:<?php echo htmlspecialchars($cfg['colore_primario']);?>;
  --header-da:<?php echo htmlspecialchars($cfg['colore_riga_dispari']??'#0a1628');?>;
  --header-a:<?php echo htmlspecialchars($cfg['colore_riga_dispari']??'#0a1628');?>;
  --tasto-da:<?php echo htmlspecialchars($cfg['colore_testo']??'#1565c0');?>;
  --tasto-a:<?php echo htmlspecialchars($cfg['colore_riga_pari']??'#0d47a1');?>;
  --tasto-bordo:rgba(100,160,255,0.35);
}
html,body{width:100%;height:100%;overflow:hidden;font-family:'Segoe UI',system-ui,-apple-system,Arial,sans-serif;<?php echo $bgCSS?>color:#fff;user-select:none;}
body::before{content:'';position:fixed;inset:0;z-index:0;pointer-events:none;background:radial-gradient(ellipse 80% 50% at 20% 20%,rgba(255,255,255,.04) 0%,transparent 60%),radial-gradient(ellipse 60% 40% at 80% 80%,rgba(255,255,255,.03) 0%,transparent 60%);}
.kiosk-wrap{position:relative;z-index:1;display:flex;flex-direction:column;width:100vw;height:100vh;}

/* HEADER */
.kiosk-header{flex:0 0 auto;display:flex;align-items:center;justify-content:space-between;padding:clamp(10px,2vh,22px) clamp(16px,3vw,48px);background:linear-gradient(135deg,var(--header-da) 0%,var(--header-a) 100%);border-bottom:2px solid rgba(255,255,255,.1);box-shadow:0 4px 30px rgba(0,0,0,.4);gap:16px;min-height:clamp(70px,11vh,120px);animation:slideDown .5s ease both;}
.header-logo{flex:0 0 auto;display:flex;align-items:center;}
.header-logo img{height:clamp(40px,7vh,80px);max-width:clamp(80px,12vw,180px);object-fit:contain;display:block;padding:clamp(6px,1vh,10px) clamp(10px,1.4vw,16px);background:rgba(255,255,255,.94);border-radius:clamp(8px,1.2vw,14px);border:1px solid rgba(255,255,255,.7);box-shadow:0 2px 14px rgba(0,0,0,.22),inset 0 1px 0 rgba(255,255,255,.85);}
.header-logo-ph{width:clamp(44px,7vh,72px);height:clamp(44px,7vh,72px);border-radius:50%;background:linear-gradient(135deg,var(--accento) 0%,var(--tasto-da) 100%);display:flex;align-items:center;justify-content:center;font-size:clamp(18px,3.5vh,36px);font-weight:900;color:#fff;box-shadow:0 0 20px rgba(66,165,245,.4);}
.header-center{flex:1;text-align:center;padding:0 12px;}
.header-nome{font-size:clamp(16px,2.8vw,42px);font-weight:800;letter-spacing:.06em;color:#fff;text-shadow:0 2px 12px rgba(0,0,0,.5);line-height:1.1;}
.header-sub{font-size:clamp(10px,1.1vw,17px);color:var(--accento);margin-top:4px;font-weight:400;letter-spacing:.06em;opacity:.9;}
.header-clock{flex:0 0 auto;text-align:right;min-width:clamp(80px,11vw,170px);}
.clock-time{font-size:clamp(22px,3.2vw,52px);font-weight:700;font-variant-numeric:tabular-nums;color:#fff;line-height:1;letter-spacing:.05em;text-shadow:0 2px 12px rgba(0,0,0,.4);}
.clock-date{font-size:clamp(9px,1vw,15px);color:var(--accento);margin-top:4px;letter-spacing:.04em;font-weight:500;}

/* BODY: 3 colonne — sidebar | media | turni */
.kiosk-body{flex:1 1 auto;display:flex;overflow:hidden;min-height:0;}

/* SIDEBAR SINISTRA — storico */
.kiosk-sidebar{flex:0 0 clamp(180px,18vw,250px);display:flex;flex-direction:column;background:rgba(10,22,40,.78);border-right:1px solid rgba(255,255,255,.07);overflow:hidden;}
.sb-section{padding:clamp(8px,1.2vh,14px) clamp(10px,1.4vw,16px);border-bottom:1px solid rgba(255,255,255,.06);}
.sb-title{font-size:clamp(9px,.85vw,11px);font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--accento);opacity:.8;margin-bottom:6px;}
.sb-logo-box{display:flex;align-items:center;justify-content:center;padding:clamp(6px,.8vh,10px) 0;}
.sb-logo-box img{max-height:clamp(32px,5vh,60px);max-width:85%;object-fit:contain;display:block;padding:clamp(5px,.8vh,8px) clamp(8px,1.2vw,12px);background:rgba(255,255,255,.94);border-radius:clamp(6px,1vw,10px);border:1px solid rgba(255,255,255,.7);box-shadow:0 2px 10px rgba(0,0,0,.2),inset 0 1px 0 rgba(255,255,255,.85);}
.sb-logo-ph{width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,var(--accento),var(--tasto-da));display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:900;margin:0 auto;}
.sb-storico{flex:1 1 auto;overflow-y:auto;padding:clamp(6px,.8vh,10px) clamp(10px,1.4vw,16px);}
.sb-storico::-webkit-scrollbar{width:3px}
.sb-storico::-webkit-scrollbar-thumb{background:rgba(255,255,255,.15);border-radius:2px}
.storico-list{list-style:none;display:flex;flex-direction:column;gap:5px;}
.storico-item{display:grid;grid-template-columns:26px 1fr auto;align-items:center;gap:7px;padding:5px 8px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:7px;}
.storico-badge{width:24px;height:24px;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0;background:linear-gradient(135deg,var(--tasto-da),var(--tasto-a));border:1px solid var(--tasto-bordo);}
.storico-num{font-size:clamp(12px,1.3vw,17px);font-weight:800;color:var(--accento);line-height:1;}
.storico-sp{font-size:10px;opacity:.4;line-height:1;margin-top:2px;}
.storico-ora{font-size:9px;opacity:.3;white-space:nowrap;align-self:flex-start;}
.storico-empty{text-align:center;opacity:.25;font-size:11px;padding:16px 0;}

/* CENTRO — multimedia */
.kiosk-media{flex:1 1 0;display:flex;align-items:center;justify-content:center;padding:clamp(14px,2vh,28px);overflow:hidden;min-width:0;}
.media-box{width:100%;height:100%;border-radius:clamp(12px,1.5vw,20px);background:rgba(10,22,40,.65);border:1px solid rgba(255,255,255,.07);box-shadow:0 8px 40px rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative;}
.media-box img,.media-box video{width:100%;height:100%;object-fit:contain;border-radius:inherit;}
.media-placeholder{text-align:center;opacity:.18;}
.media-placeholder svg{width:clamp(48px,8vw,96px);height:auto;margin-bottom:12px;}
.media-placeholder p{font-size:clamp(10px,1.1vw,14px);letter-spacing:.08em;text-transform:uppercase;}

/* COLONNA DESTRA — turni */
.kiosk-turni{flex:1 1 0;display:flex;flex-direction:column;gap:clamp(6px,1vh,12px);padding:clamp(10px,1.2vh,16px) clamp(10px,1.2vw,16px);background:rgba(10,22,40,.78);border-left:1px solid rgba(255,255,255,.07);overflow:hidden;}
.kiosk-turni::-webkit-scrollbar{width:3px}
.kiosk-turni::-webkit-scrollbar-thumb{background:rgba(255,255,255,.15);border-radius:2px}
.kiosk-turni-title{font-size:clamp(9px,.85vw,11px);font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--accento);opacity:.8;margin-bottom:4px;padding-bottom:8px;border-bottom:1px solid rgba(255,255,255,.06);}

/* Card turno — compatta, orientata orizzontalmente */
.card-turno{position:relative;display:flex;align-items:center;gap:clamp(14px,2vw,28px);padding:clamp(12px,2vh,28px) clamp(14px,2vw,28px);border:2px solid var(--tasto-bordo);border-radius:clamp(10px,1.2vw,16px);background:linear-gradient(145deg,var(--tasto-da) 0%,var(--tasto-a) 100%);color:#fff;overflow:hidden;box-shadow:0 6px 24px rgba(0,0,0,.35),inset 0 1px 0 rgba(255,255,255,.12);animation:fadeUp .45s ease both;flex:1 1 0;min-height:0;}
.card-turno::before{content:'';position:absolute;inset:0;pointer-events:none;border-radius:inherit;background:linear-gradient(135deg,rgba(255,255,255,.12) 0%,rgba(255,255,255,.03) 40%,transparent 60%);}
.ct-badge{flex:0 0 auto;width:clamp(80px,8vh,130px);height:clamp(80px,8vh,130px);border-radius:clamp(10px,1.2vw,18px);background:rgba(0,0,0,.25);border:1px solid rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:clamp(36px,5.5vh,72px);font-weight:900;letter-spacing:.02em;flex-shrink:0;}
.ct-info{flex:1 1 auto;min-width:0;display:flex;flex-direction:column;gap:3px;}
.ct-nome{font-size:clamp(14px,1.8vh,26px);font-weight:600;letter-spacing:.03em;opacity:.65;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.ct-sportello-label{font-size:clamp(10px,1.1vh,16px);opacity:.4;letter-spacing:.06em;text-transform:uppercase;line-height:1;}
.ct-sportello-val{font-size:clamp(18px,2.4vh,38px);font-weight:700;color:var(--accento);opacity:.9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.ct-num-col{flex:0 0 auto;text-align:right;}
.ct-num{font-size:clamp(60px,9vh,140px);font-weight:800;line-height:1;color:var(--accento);letter-spacing:-.02em;text-shadow:0 0 20px rgba(66,165,245,.45);font-variant-numeric:tabular-nums;}
.ct-num.num-zero{color:rgba(255,255,255,.2);text-shadow:none;font-size:clamp(48px,7vh,110px);}
.ct-coda{font-size:clamp(11px,1.2vh,18px);opacity:.35;letter-spacing:.06em;text-transform:uppercase;text-align:right;margin-top:2px;}

/* Animazioni */
.af .ct-num{animation:aFlip .5s ease}
.as .ct-num{animation:aSlide .4s ease}
.ah .ct-num{animation:aFlash .6s ease}
.az .ct-num{animation:aZoom .5s ease}
@keyframes aFlip{0%{transform:rotateX(90deg);opacity:0}100%{transform:rotateX(0);opacity:1}}
@keyframes aSlide{0%{transform:translateY(-16px);opacity:0}100%{transform:translateY(0);opacity:1}}
@keyframes aFlash{0%,100%{opacity:1}50%{opacity:0}}
@keyframes aZoom{0%{transform:scale(1.4);opacity:0}100%{transform:scale(1);opacity:1}}

/* FOOTER */
.kiosk-footer{flex:0 0 auto;background:linear-gradient(135deg,var(--header-da) 0%,var(--header-a) 100%);border-top:1px solid rgba(255,255,255,.07);height:clamp(52px,7vh,80px);display:flex;align-items:center;overflow:hidden;padding:0 clamp(12px,2vw,24px);}
.ticker-label{font-size:clamp(12px,1.1vw,16px);font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--accento);margin-right:16px;white-space:nowrap;flex-shrink:0;}
.ticker-track{flex:1;overflow:hidden;}
.ticker-inner{white-space:nowrap;display:inline-block;animation:tickerMove linear infinite;}
.ticker-inner span{font-size:clamp(18px,2.2vw,32px);font-weight:700;opacity:.95;padding-right:120px;letter-spacing:.02em;}
@keyframes tickerMove{from{transform:translateX(100vw)}to{transform:translateX(-100%)}}
@keyframes slideDown{from{transform:translateY(-28px);opacity:0}to{transform:none;opacity:1}}
@keyframes fadeUp{from{transform:translateY(20px);opacity:0}to{transform:none;opacity:1}}


</style>
</head>
<body>
<audio id="audioPlayer" preload="auto"></audio>

<div class="kiosk-wrap">

<header class="kiosk-header">
  <div class="header-logo">
    <?php if($hasLogo):?><img src="immagini/logo_monitor2.<?php echo $logoExt;?>" alt="Logo">
    <?php else:?><div class="header-logo-ph">+</div><?php endif;?>
  </div>
  <div class="header-center">
    <div class="header-nome"><?php echo htmlspecialchars($cfg['titolo_struttura']);?></div>
    <div class="header-sub">Gestione Code &mdash; Monitor Sala Attesa</div>
  </div>
  <div class="header-clock">
    <div class="clock-time" id="htime">--:--:--</div>
    <div class="clock-date" id="hdate"></div>
  </div>
</header>

<div class="kiosk-body">

  <!-- SIDEBAR SINISTRA: storico -->
  <aside class="kiosk-sidebar">
    <div class="sb-section">
      <div class="sb-logo-box">
        <?php if($hasLogo):?><img src="immagini/logo_monitor2.<?php echo $logoExt;?>" alt="Logo">
        <?php else:?><div class="sb-logo-ph">+</div><?php endif;?>
      </div>
    </div>
    <div class="sb-section"><div class="sb-title">Ultimi chiamati</div></div>
    <div class="sb-storico">
      <ul class="storico-list" id="storico-list"><li class="storico-empty">In attesa di chiamate&hellip;</li></ul>
    </div>
  </aside>

  <!-- CENTRO: multimedia -->
  <div class="kiosk-media">
    <div class="media-box" id="mediaBox">
      <div class="media-placeholder" id="mediaPlaceholder">
        <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="10" width="56" height="36" rx="4" stroke="white" stroke-width="3"/><path d="M26 24l14 8-14 8V24z" fill="white"/><rect x="20" y="50" width="24" height="3" rx="1.5" fill="white"/></svg>
        <p>Contenuti multimediali</p>
      </div>
    </div>
  </div>

  <!-- COLONNA DESTRA: turni -->
  <div class="kiosk-turni">
    <div class="kiosk-turni-title">Sportelli attivi</div>
    <?php foreach($turni as $i=>$t):?>
    <div class="card-turno" id="card-<?php echo $t['id'];?>" style="animation-delay:<?php echo($i*.1);?>s">
      <div class="ct-badge"><?php echo $t['lettera'];?></div>
      <div class="ct-info">
        <div class="ct-nome"><?php echo $t['nome'];?></div>
        <div class="ct-sportello-label">Sportello</div>
        <div class="ct-sportello-val" id="sp-<?php echo $t['id'];?>"><?php echo $t['postazione'];?></div>
      </div>
      <div class="ct-num-col">
        <div class="ct-num<?php echo $t['numero']===0?' num-zero':'';?>" id="num-<?php echo $t['id'];?>">
          <?php echo $t['numero']>0?str_pad($t['numero'],3,'0',STR_PAD_LEFT):'&ndash;&ndash;&ndash;';?>
        </div>
        <div class="ct-coda" id="coda-<?php echo $t['id'];?>"><?php echo $t['in_coda'];?> in attesa</div>
      </div>
    </div>
    <?php endforeach;?>
  </div>

</div>

<footer class="kiosk-footer">
  <?php if($cfg['news_attivo']&&$cfg['news_testo']):?>
  <span class="ticker-label">Avvisi</span>
  <div class="ticker-track"><div class="ticker-inner" style="animation-duration:<?php echo max(10,intval($cfg['news_velocita']));?>s"><span><?php echo htmlspecialchars($cfg['news_testo']);?></span></div></div>
  <?php else:?><span style="opacity:.2;font-size:11px;letter-spacing:.1em">MONITOR SALA ATTESA</span><?php endif;?>
</footer>

</div>
<script id="bp" type="application/json"><?php echo json_encode($turni,JSON_HEX_TAG|JSON_HEX_AMP);?></script>
<script>
(function(){
var GIORNI=['Domenica','Luned\u00ec','Marted\u00ec','Mercoled\u00ec','Gioved\u00ec','Venerd\u00ec','Sabato'];
var MESI=['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
function clock(){var n=new Date();document.getElementById('htime').textContent=String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0')+':'+String(n.getSeconds()).padStart(2,'0');document.getElementById('hdate').textContent=GIORNI[n.getDay()]+' '+n.getDate()+' '+MESI[n.getMonth()]+' '+n.getFullYear();}
clock();setInterval(clock,1000);
var stato={};var storico=[];
var animMap={flip:'af',slide:'as',flash:'ah',zoom:'az'};
var aCls=animMap['<?php echo htmlspecialchars($cfg["animazione"]);?>']||'af';
var init=JSON.parse(document.getElementById('bp').textContent);
var codaMap={};
init.forEach(function(t){stato[t.id]={num:t.numero,sp:t.postazione};codaMap[t.id]=t.coda_num||0;});
function setNum(id,num,sp,coda){
  var prev=stato[id]||{};var changed=prev.num!==num||prev.sp!==sp;
  stato[id]={num:num,sp:sp};
  var $card=$('#card-'+id);
  if(changed&&num>0){$card.removeClass('af as ah az').addClass(aCls);setTimeout(function(){$card.removeClass('af as ah az');},700);if(prev.num!==num)addStorico(id,num,sp);}
  $('#num-'+id).html(num>0?String(num).padStart(3,'0'):'&ndash;&ndash;&ndash;').toggleClass('num-zero',num===0);
  $('#sp-'+id).text(sp||'-');
  $('#coda-'+id).text(coda+' in attesa');
}
function addStorico(id,num,sp){
  var id2=parseInt(id,10);var td=init.find(function(t){return t.id===id2;});var lettera=td?td.lettera:'?';
  var n=new Date();var ora=String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0');
  storico.unshift({lettera:lettera,num:num,sp:sp,ora:ora});
  if(storico.length>10)storico.pop();renderStorico();
}
function renderStorico(){
  var $ul=$('#storico-list');
  if(!storico.length){$ul.html('<li class="storico-empty">In attesa di chiamate&hellip;</li>');return;}
  $ul.html(storico.map(function(s){return '<li class="storico-item"><div class="storico-badge">'+s.lettera+'</div><div><div class="storico-num">'+String(s.num).padStart(3,'0')+'</div><div class="storico-sp">'+s.sp+'</div></div><div class="storico-ora">'+s.ora+'</div></li>';}).join(''));
}
var _ipmonitor='<?php echo addslashes($ipclient);?>';
var _pollBusy=false;
function poll(){
  if(_pollBusy)return;
  _pollBusy=true;
  $.ajax({url:'chiamanumero.php',type:'POST',data:{action:'chiamanumero',ipmonitor:_ipmonitor},
  success:function(resp){
    if(resp&&resp.trim()!==''){
      var p=resp.trim().split('|');
      var turno=p[0]||'',numero=parseInt(p[1])||0,sportello=p[2]||'-',idturno=p[4]||p[0]||'';
      setNum(idturno,numero,sportello,Math.max(0,(codaMap[idturno]||0)-numero));
      // Sblocca il poll subito, audio in parallelo non bloccante
      _pollBusy=false;
      setTimeout(poll,1200);
      _log('POLL: dati='+resp.trim()+' turno='+turno+' num='+p[1]+' sp='+sportello);
      playAudio(turno,p[1],sportello);
    } else {
      _pollBusy=false;
      setTimeout(poll,1500);
    }
  },
  error:function(){_pollBusy=false;setTimeout(poll,3000);}
  });
}
// ---- SISTEMA TTS ----
// --- DEBUG LOG ---
function _log(msg){
  try{
    var xhr=new XMLHttpRequest();
    xhr.open('POST','/tts_log.php',true);
    xhr.setRequestHeader('Content-Type','text/plain');
    xhr.send('['+new Date().toISOString().substr(11,12)+'] '+msg);
  }catch(e){}
}
// --- FINE DEBUG ---
var _ttsQueue=[];
var _ttsSpeaking=false;
var _ttsSafetyTimer=null;
var _useFullyTTS=(typeof fully!=='undefined'&&typeof fully.textToSpeech==='function');
var _webSpeechReady=_useFullyTTS;
var _audioUnlocked=_useFullyTTS; // su Fully non serve sblocco
var _ttsVoce=<?php echo json_encode($ttsVoceInit);?>;
var _ttsVel=<?php echo json_encode($ttsVelInit);?>;
var _ttsCfgRev=<?php echo json_encode($ttsRevInit);?>;

function _refreshTtsCfg(d){
  if(!d)return;
  if(typeof d.tts_voce==='string'&&d.tts_voce){ _ttsVoce=d.tts_voce; }
  if(typeof d.tts_velocita!=='undefined'){
    var v=parseInt(d.tts_velocita,10);
    if(!isNaN(v)&&v>0){ _ttsVel=v; }
  }
  if(typeof d.tts_rev!=='undefined'){
    var r=parseInt(d.tts_rev,10);
    if(!isNaN(r)&&r>=0){ _ttsCfgRev=r; }
  }
}

// Monitor non interattivo: tenta unlock automatico periodico, senza richiedere tap/click
function _tryAutoUnlockAudio(){
  if(_audioUnlocked||_useFullyTTS)return;
  var silent='data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQAAAAA=';
  var probe=document.getElementById('audioPlayer')||new Audio();
  probe.muted=true;
  probe.src=silent;
  var p=probe.play();
  if(!p||typeof p.then!=='function')return;
  p.then(function(){
    _audioUnlocked=true;
    try{ probe.pause(); }catch(e){}
    probe.currentTime=0;
    probe.muted=false;
    _log('audio auto-unlocked');
    setTimeout(_ttsProcessQueue,100);
  }).catch(function(e){
    _audioUnlocked=false;
    _log('audio unlock blocked: '+(e&&e.name?e.name:'unknown'));
  });
}
_tryAutoUnlockAudio();
setInterval(_tryAutoUnlockAudio,15000);

// Fallback: audio server-side via espeak-ng (tts_audio.php)
var _ttsAudio=document.getElementById('audioPlayer');
function _ttsUseAudioFallback(testo){
  if(!_audioUnlocked&&!_useFullyTTS){ _tryAutoUnlockAudio(); }
  _ttsSpeaking=true;
  _log('AUDIO FALLBACK: '+testo);
  console.log('[TTS] _ttsUseAudioFallback START:', testo);
  var url='/tts_audio.php?t='+encodeURIComponent(testo)
    +'&voce='+encodeURIComponent(_ttsVoce||'ElsaNeural')
    +'&vel='+encodeURIComponent(_ttsVel||135)
    +'&cfgv='+encodeURIComponent(_ttsCfgRev||0);
  console.log('[TTS] fetch URL:', url);
  if(_ttsAudio){ try{_ttsAudio.pause();_ttsAudio.src='';}catch(e){} }
  if(!_ttsAudio){ _ttsAudio=new Audio(); }
  _ttsAudio.src=url;
  _ttsAudio.muted=false;
  _ttsAudio.oncanplaythrough=function(){ console.log('[TTS] audio canplaythrough, duration='+_ttsAudio.duration); };
  _ttsAudio.onplaying=function(){ console.log('[TTS] audio PLAYING'); };
  _ttsAudio.onended=function(){
    console.log('[TTS] audio ENDED');
    _log('AUDIO FALLBACK ended');
    _ttsSpeaking=false;
    setTimeout(_ttsProcessQueue,300);
  };
  _ttsAudio.onerror=function(e){
    console.error('[TTS] audio ERROR code='+(_ttsAudio.error?_ttsAudio.error.code:'?')+' msg='+(_ttsAudio.error?_ttsAudio.error.message:'?'));
    _log('AUDIO FALLBACK error: '+e.type);
    _ttsSpeaking=false;
    setTimeout(_ttsProcessQueue,300);
  };
  var p=_ttsAudio.play();
  console.log('[TTS] play() called, promise=', p);
  p.then(function(){ console.log('[TTS] play() promise RESOLVED'); })
   .catch(function(e){
    console.error('[TTS] play() REJECTED:', e.name, e.message);
    _log('AUDIO FALLBACK play() rejected: '+e);
    _ttsSpeaking=false;
    setTimeout(_ttsProcessQueue,300);
  });
} // Fully non ha bisogno di unlock

// Chrome blocca speechSynthesis senza gesto utente: sblocca al primo touch/click
function _unlockWebSpeech(){
  if(_webSpeechReady||_useFullyTTS||!window.speechSynthesis)return;
  _webSpeechReady=true;
  var u=new SpeechSynthesisUtterance(' ');
  u.volume=0; u.rate=10;
  try{ window.speechSynthesis.speak(u); }catch(e){}
}
document.addEventListener('touchstart',_unlockWebSpeech,{once:true,passive:true});
document.addEventListener('click',_unlockWebSpeech,{once:true});

function playAudio(turno,numero,sportello){
  _log('playAudio called t='+turno+' n='+numero+' sp='+sportello);
  _ttsQueue.push({t:turno,n:numero,s:sportello});
  _ttsProcessQueue();
}

function _ttsProcessQueue(){
  _log('_ttsProcessQueue: speaking='+_ttsSpeaking+' queue='+_ttsQueue.length+' fullyAPI='+_useFullyTTS+' unlocked='+_audioUnlocked);
  if(_ttsSpeaking||!_ttsQueue.length)return;
  if(!_audioUnlocked&&!_useFullyTTS){ _tryAutoUnlockAudio(); return; }
  var item=_ttsQueue.shift();
  _ttsSpeak(item.t,item.n,item.s);
}

function _ttsSpeak(turno,numero,sportello){
  _log('_ttsSpeak ENTER t='+turno+' n='+numero);
  _ttsSpeaking=true;
  var testo='Turno '+turno+', numero '+numero+'. Recarsi allo sportello '+sportello;

  if(_useFullyTTS){
    _log('TTS via fully.textToSpeech: '+testo);
    // Fully Kiosk: API nativa Android, nessuna restrizione audio
    try{
      if(typeof fully.setTextToSpeechLanguage==='function') fully.setTextToSpeechLanguage('it-IT');
      fully.textToSpeech(testo);
      _log('fully.textToSpeech OK');
    }catch(e){ _log('fully.textToSpeech ERROR: '+e); console.warn('Fully TTS error',e); }
    // Stima durata: attendi prima di passare al prossimo
    var ms=Math.max(3000, testo.length*75);
    _ttsSafetyTimer=setTimeout(function(){
      _ttsSpeaking=false; _ttsSafetyTimer=null;
      setTimeout(_ttsProcessQueue,300);
    }, ms);
    return;
  }

  // Web Speech API non funziona senza gesture su monitor non interattivi.
  // _webSpeechReady=false finché l'utente non interagisce → usa sempre tts_audio.php
  if(!window.speechSynthesis||!_webSpeechReady){ _log('uso fallback audio (tts_audio.php)'); _ttsSpeaking=false; _ttsUseAudioFallback(testo); return; }

  function doSpeak(){
    var u=new SpeechSynthesisUtterance(testo);
    u.lang='it-IT'; u.rate=0.9; u.pitch=1; u.volume=1;
    var voci=window.speechSynthesis.getVoices();
    var itVoice=voci.find(function(v){ return v.lang&&v.lang.toLowerCase().indexOf('it')===0; });
    if(itVoice) u.voice=itVoice;
    var _done=false;
    function onDone(){
      if(_done)return; _done=true;
      if(_ttsSafetyTimer){ clearTimeout(_ttsSafetyTimer); _ttsSafetyTimer=null; }
      _ttsSpeaking=false;
      setTimeout(_ttsProcessQueue,500);
    }
    u.onend=onDone;
    u.onerror=function(e){ console.warn('TTS err',e.error); onDone(); };
    _ttsSafetyTimer=setTimeout(onDone,20000);
    _log('speechSynthesis.speak() called, voices='+voci.length+' itVoice='+(itVoice?itVoice.name:'none'));
    window.speechSynthesis.speak(u);
  }

  if(window.speechSynthesis.speaking||window.speechSynthesis.pending){
    window.speechSynthesis.cancel();
    setTimeout(doSpeak,300);
    return;
  }
  var voci=window.speechSynthesis.getVoices();
  if(voci.length>0){
    doSpeak();
  } else {
    var _vReady=false;
    window.speechSynthesis.onvoiceschanged=function(){
      if(_vReady)return; _vReady=true;
      window.speechSynthesis.onvoiceschanged=null;
      doSpeak();
    };
    setTimeout(function(){ if(!_vReady){ _vReady=true; doSpeak(); } },600);
  }
}

// Watchdog: sblocca stuck (solo Web Speech API)
setInterval(function(){
  if(_useFullyTTS||!window.speechSynthesis)return;
  // Se il fallback audio è attivo e sta riproducendo, non interferire
  if(_ttsAudio && !_ttsAudio.paused && !_ttsAudio.ended) return;
  if(window.speechSynthesis.paused) window.speechSynthesis.resume();
  if(_ttsSpeaking&&!window.speechSynthesis.speaking&&!window.speechSynthesis.pending){
    _ttsSpeaking=false;
    if(_ttsSafetyTimer){ clearTimeout(_ttsSafetyTimer); _ttsSafetyTimer=null; }
    setTimeout(_ttsProcessQueue,100);
  }
},5000);
// ----------------------------------------
setTimeout(poll,500);
var _tickerTesto=null;
function pollTicker(){$.getJSON("get_monitor2_cfg.php",function(d){_refreshTtsCfg(d);var $ft=$(".kiosk-footer");if(d.news_attivo&&d.news_testo){if(d.news_testo!==_tickerTesto){_tickerTesto=d.news_testo;$ft.html("<span class=\"ticker-label\">Avvisi<\/span><div class=\"ticker-track\"><div class=\"ticker-inner\" style=\"animation-duration:"+Math.max(10,parseInt(d.news_velocita))+"s\"><span>"+$("<div>").text(d.news_testo).html()+"<\/span><\/div><\/div>");}}else{$ft.html("<span style=\"opacity:.2;font-size:11px;letter-spacing:.1em\">MONITOR SALA ATTESA<\/span>");}}).always(function(){setTimeout(pollTicker,30000);});}
setTimeout(pollTicker,30000);
// Refresh automatico ogni mattina alle 07:00 (dopo reset notturno cron)
function scheduleMorningRefresh(){
  function msUntilNext(){
    var now=new Date(),next=new Date();
    next.setHours(7,0,0,0);
    if(now>=next)next.setDate(next.getDate()+1);
    return next-now;
  }
  setTimeout(function(){window.location.reload();},msUntilNext());
}
scheduleMorningRefresh();
<?php if($cfg['mostra_multimedia']):?>
var _mIdx=0,_mItems=[];
var _mDurata=<?php echo max(1,intval($cfg['multimedia_durata']));?> * 1000;
var _mServizio=<?php echo json_encode($cfg['multimedia_immagine_servizio'] ? 'immagini/' . $cfg['multimedia_immagine_servizio'] : '');?>;
var _mPollTimer=null;
var _mActive=false;
function showServiceImage(){
  if (_mServizio) {
    $('#mediaPlaceholder').hide();
    $('#mediaBox').html('<img src="'+_mServizio+'" alt="" style="width:100%;height:100%;object-fit:contain;border-radius:inherit;">');  
  } else {
    $('#mediaBox').html('<div class="media-placeholder" id="mediaPlaceholder"><svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="10" width="56" height="36" rx="4" stroke="white" stroke-width="3"/><path d="M26 24l14 8-14 8V24z" fill="white"/><rect x="20" y="50" width="24" height="3" rx="1.5" fill="white"/></svg><p>Contenuti multimediali</p></div>');
  }
}
function loadMedia(){
  $.when(
    $.getJSON('getimmagini.php'),
    $.getJSON('getvideo.php')
  ).done(function(imgRes, vidRes){
    var newItems = [];
    var imgs = imgRes[0]; var vids = vidRes[0];
    if (Array.isArray(imgs) && imgs.length) imgs.forEach(function(img){ newItems.push({type:'img',src:img}); });
    if (Array.isArray(vids) && vids.length) vids.forEach(function(vid){ newItems.push({type:'vid',src:vid}); });
    _mItems = newItems;

    if (_mItems.length) {
      if (_mPollTimer) { clearInterval(_mPollTimer); _mPollTimer = null; }
      _mActive = true;
      _mIdx = 0;
      showMedia();
    } else {
      _mActive = false;
      showServiceImage();
      scheduleMediaPoll();
    }
  }).fail(function(){
    _mActive = false;
    showServiceImage();
    scheduleMediaPoll();
  });
}
function scheduleMediaPoll(){
  if (_mActive || _mPollTimer) return;
  _mPollTimer = setInterval(function(){
    if (_mActive) { clearInterval(_mPollTimer); _mPollTimer = null; return; }
    loadMedia();
  }, 15000);
}
function showMedia(){
  if (!_mActive) return;
  if (!_mItems.length) { _mActive = false; showServiceImage(); scheduleMediaPoll(); return; }

  // Al termine di ogni ciclo completo, ricarica la lista per rilevare aggiornamenti o cancellazioni
  if (_mIdx > 0 && (_mIdx % _mItems.length) === 0) {
    loadMedia();
    return;
  }

  var item = _mItems[_mIdx % _mItems.length];
  var $box = $('#mediaBox');
  $('#mediaPlaceholder').hide();

  if (item.type === 'img') {
    $box.html('<img id="mediaImg" src="'+item.src+'" alt="" style="width:100%;height:100%;object-fit:contain;border-radius:inherit;">');  
    $('#mediaImg').on('error', function(){ _mIdx++; showMedia(); });
    _mIdx++;
    setTimeout(showMedia, _mDurata);
  } else if (item.type === 'vid') {
    $box.html('<video id="mediaVideo" src="'+item.src+'" autoplay muted playsinline style="width:100%;height:100%;object-fit:contain;border-radius:inherit;"></video>');  
    var video = document.getElementById('mediaVideo');
    video.onended = function(){ _mIdx++; setTimeout(showMedia, _mDurata); };
    video.onerror = function(){ _mIdx++; showMedia(); };
  }
}
loadMedia();
<?php endif;?>
})();
</script>
</body>
</html>
