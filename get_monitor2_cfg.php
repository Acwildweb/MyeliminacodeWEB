<?php
include 'connect.php';
$defaults=['news_testo'=>'','news_attivo'=>1,'news_velocita'=>60,'titolo_struttura'=>'OSPEDALE','colore_primario'=>'#42a5f5','multimedia_durata'=>5,'multimedia_immagine_servizio'=>''];
$cfg=$defaults;
$rs=mysqli_query($conn,"SELECT news_testo,news_attivo,news_velocita,multimedia_durata,multimedia_immagine_servizio FROM config_monitor2 WHERE id=1 LIMIT 1");
if($rs&&$row=mysqli_fetch_assoc($rs)){foreach($defaults as $k=>$v){if(isset($row[$k])&&$row[$k]!==null&&$row[$k]!=='')$cfg[$k]=$row[$k];}}
mysqli_close($conn);
$ttsFile=__DIR__.'/totem_ui_config.json';
$ttsCfg=file_exists($ttsFile)?(json_decode(file_get_contents($ttsFile),true)?:[]):[];
$ttsVoce=isset($ttsCfg['tts_voce'])?$ttsCfg['tts_voce']:'ElsaNeural';
$ttsVel=isset($ttsCfg['tts_velocita'])?intval($ttsCfg['tts_velocita']):135;
$ttsRev=file_exists($ttsFile)?intval(@filemtime($ttsFile)):0;
header('Content-Type: application/json');
echo json_encode([
	'news_testo'=>$cfg['news_testo'],
	'news_attivo'=>(int)$cfg['news_attivo'],
	'news_velocita'=>(int)$cfg['news_velocita'],
	'multimedia_durata'=>(int)$cfg['multimedia_durata'],
	'multimedia_immagine_servizio'=>$cfg['multimedia_immagine_servizio'],
	'tts_voce'=>$ttsVoce,
	'tts_velocita'=>$ttsVel,
	'tts_rev'=>$ttsRev
]);
