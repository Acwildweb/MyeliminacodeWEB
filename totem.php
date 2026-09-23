<?php
// ══════════════════════════════════════════════════════════════
//  totem.php  –  Interfaccia kiosk moderna, generata dinamicamente
// ══════════════════════════════════════════════════════════════
include 'connect.php';
require_once __DIR__ . '/tocron/orari_version_lib.php';

// Endpoint leggero per auto-reload kiosk (nessun side-effect)
if (isset($_GET['orari_ver'])) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    try {
        $v = getOrariConfigVersion($conn);
        mysqli_close($conn);
        echo json_encode(['ok' => true, 'v' => $v], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'v' => '']);
    }
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// ── Configurazione UI ─────────────────────────────────────────
$uiConfigFile = __DIR__ . '/totem_ui_config.json';
$ui = [];
if (file_exists($uiConfigFile)) {
    $ui = json_decode(file_get_contents($uiConfigFile), true) ?: [];
}
$ui = array_merge([
    'nome_struttura'        => 'MySanitario',
    'sottotitolo'           => 'Benvenuto — Seleziona il servizio desiderato',
    'logo_path'             => '',
    'sfondo_tipo'           => 'gradiente',
    'sfondo_gradiente_da'   => '#0d1b3e',
    'sfondo_gradiente_a'    => '#1a3a6e',
    'sfondo_colore'         => '#0d1b3e',
    'header_gradiente_da'   => '#0a1628',
    'header_gradiente_a'    => '#112244',
    'tasto_gradiente_da'    => '#1565c0',
    'tasto_gradiente_a'     => '#0d47a1',
    'tasto_hover_da'        => '#1976d2',
    'tasto_hover_a'         => '#1565c0',
    'tasto_bordo_colore'    => 'rgba(100,160,255,0.35)',
    'tasto_testo_colore'    => '#ffffff',
    'accento_colore'        => '#42a5f5',
    'header_testo_colore'   => '#ffffff',
    'footer_testo'          => 'Grazie per la vostra visita',
    'mostra_footer'         => true,
    'colonne_max'           => 3,
    'icone_abilitate'       => true,
    'animazioni_abilitate'  => true,
    // Comportamento pulsanti fuori orario operativo
    'pulsanti_stato_attivo'     => true,
    'pulsanti_modo_chiuso'      => 'evidenzia',
    'pulsanti_mostra_messaggio' => true,
    'pulsanti_colore_chiuso'    => '#5b6573',
    'pulsanti_testo_chiuso'     => 'Chiuso',
], $ui);

// ── Leggi turni dal database ──────────────────────────────────
$turni = [];
$sql_cfg = "SELECT ct.idturno, t.turno AS nometurno, t.desstato AS descservizio
            FROM configturnitotem ct
            INNER JOIN configtotem c ON c.idconfigtotem = ct.idconfigtotem
            INNER JOIN turni t ON t.ID_turno = ct.idturno
            ORDER BY ct.idturno";
$res_cfg = mysqli_query($conn, $sql_cfg);
if ($res_cfg && mysqli_num_rows($res_cfg) > 0) {
    while ($r = mysqli_fetch_assoc($res_cfg)) {
        $turni[] = ['idturno' => $r['idturno'], 'nometurno' => $r['nometurno'], 'descservizio' => $r['descservizio']];
    }
} else {
    $res_all = mysqli_query($conn, "SELECT ID_turno AS idturno, turno AS nometurno, desstato AS descservizio FROM turni ORDER BY ID_turno");
    if ($res_all) {
        while ($r = mysqli_fetch_assoc($res_all)) {
            $turni[] = ['idturno' => $r['idturno'], 'nometurno' => $r['nometurno'], 'descservizio' => $r['descservizio']];
        }
    }
}

// ── Orari operativi SETTIMANALI per ciascun turno ─────────────
// Stessa logica di prendinumero.php: turno -> operazioni_turni -> operazioni_giorni
// giorno: 0=Domenica ... 6=Sabato. Gli orari sono memorizzati come 'HHMM'.
// Si incorpora l'intera settimana: il client sceglie il giorno corrente in base
// al proprio orologio, così il cambio giorno a mezzanotte non richiede ricaricamenti.
$orariTurniSett = [];
$res_orari = mysqli_query(
    $conn,
    "SELECT ot.id_turno, og.giorno, og.ora_inizio, og.ora_fine
     FROM operazioni_turni ot
     INNER JOIN operazioni_giorni og ON ot.id_operazione = og.id_operazione"
);
if ($res_orari) {
    while ($r = mysqli_fetch_assoc($res_orari)) {
        $ini = str_replace(':', '', (string)$r['ora_inizio']);
        $fin = str_replace(':', '', (string)$r['ora_fine']);
        if ($ini === '' || $fin === '') continue;
        $g = (string)(int)$r['giorno'];
        $orariTurniSett[(string)$r['id_turno']][$g][] = ['i' => $ini, 'f' => $fin];
    }
}
// Versione orari al momento del render (per auto-reload kiosk)
$orariVer = getOrariConfigVersion($conn);
mysqli_close($conn);

// Scurisce un colore esadecimale (#rrggbb o #rgb) di un dato fattore (0..1)
function darkenHex(string $hex, float $factor = 0.68): string {
    $hex = ltrim(trim($hex), '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return '#' . $hex;
    }
    $r = (int)round(hexdec(substr($hex, 0, 2)) * $factor);
    $g = (int)round(hexdec(substr($hex, 2, 2)) * $factor);
    $b = (int)round(hexdec(substr($hex, 4, 2)) * $factor);
    return sprintf('#%02x%02x%02x', max(0,$r), max(0,$g), max(0,$b));
}

// ── Helpers ───────────────────────────────────────────────────
$logoPath  = $ui['logo_path'] !== '' ? htmlspecialchars($ui['logo_path']) : '';
$logoFile  = $logoPath !== '' ? __DIR__ . '/' . $ui['logo_path'] : '';
$logoExist = $logoFile !== '' && file_exists($logoFile);

// ── URL endpoint stampa (server locale o agente sul totem) ────
$printerCfgFile = __DIR__ . '/printer_config.json';
$printerCfg     = file_exists($printerCfgFile) ? (json_decode(file_get_contents($printerCfgFile), true) ?: []) : [];
$printMode      = $printerCfg['print_mode']      ?? 'server';
$localAgentUrl  = $printerCfg['local_agent_url'] ?? 'http://localhost/MySanitarioConTotem/print_agent.php';
// In modalità server l'URL è relativo; in modalità agente è l'URL assoluto visto dal browser del totem
$printEndpointJs = ($printMode === 'local_agent')
    ? json_encode($localAgentUrl)
    : json_encode('stampa_diretta.php');

$nTurni     = count($turni);
$colonneMax = max(1, intval($ui['colonne_max']));
// colonne_max è sempre rispettato; non si può superare il numero di turni
$colonne = min($nTurni, $colonneMax);

if ($ui['sfondo_tipo'] === 'gradiente') {
    $bgCSS = "background: linear-gradient(160deg, {$ui['sfondo_gradiente_da']} 0%, {$ui['sfondo_gradiente_a']} 100%);";
} else {
    $bgCSS = "background-color: {$ui['sfondo_colore']};";
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($ui['nome_struttura']); ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --accento:        <?php echo $ui['accento_colore']; ?>;
    --tasto-da:       <?php echo $ui['tasto_gradiente_da']; ?>;
    --tasto-a:        <?php echo $ui['tasto_gradiente_a']; ?>;
    --tasto-hover-da: <?php echo $ui['tasto_hover_da']; ?>;
    --tasto-hover-a:  <?php echo $ui['tasto_hover_a']; ?>;
    --tasto-bordo:    <?php echo $ui['tasto_bordo_colore']; ?>;
    --tasto-testo:    <?php echo $ui['tasto_testo_colore']; ?>;
    --header-da:      <?php echo $ui['header_gradiente_da']; ?>;
    --header-a:       <?php echo $ui['header_gradiente_a']; ?>;
    --header-testo:   <?php echo $ui['header_testo_colore']; ?>;
    --colonne:        <?php echo $colonne; ?>;
    --tasto-chiuso-da:<?php echo $ui['pulsanti_colore_chiuso']; ?>;
    --tasto-chiuso-a: <?php echo darkenHex($ui['pulsanti_colore_chiuso']); ?>;
}
html, body {
    width:100%; height:100%;
    overflow:hidden;
    font-family:'Segoe UI',system-ui,-apple-system,Arial,sans-serif;
    <?php echo $bgCSS ?>
    color:#fff;
    user-select:none;
}
body::before {
    content:''; position:fixed; inset:0; z-index:0; pointer-events:none;
    background:
        radial-gradient(ellipse 80% 50% at 20% 20%, rgba(255,255,255,.04) 0%, transparent 60%),
        radial-gradient(ellipse 60% 40% at 80% 80%, rgba(255,255,255,.03) 0%, transparent 60%);
}
.kiosk-wrap { position:relative; z-index:1; display:flex; flex-direction:column; width:100vw; height:100vh; }

/* HEADER */
.kiosk-header {
    flex:0 0 auto; display:flex; align-items:center; justify-content:space-between;
    padding:clamp(10px,2vh,24px) clamp(16px,3vw,48px);
    background:linear-gradient(135deg,var(--header-da) 0%,var(--header-a) 100%);
    border-bottom:2px solid rgba(255,255,255,.1);
    box-shadow:0 4px 30px rgba(0,0,0,.4);
    gap:16px; min-height:clamp(80px,12vh,140px);
}
.header-logo { flex:0 0 auto; display:flex; align-items:center; }
.header-logo img {
    height:clamp(44px,8vh,100px); max-width:clamp(100px,14vw,220px);
    object-fit:contain; display:block;
    padding:clamp(6px,1vh,10px) clamp(10px,1.4vw,16px);
    background:rgba(255,255,255,.94);
    border-radius:clamp(8px,1.2vw,14px);
    border:1px solid rgba(255,255,255,.7);
    box-shadow:0 2px 14px rgba(0,0,0,.22), inset 0 1px 0 rgba(255,255,255,.85);
}
.header-logo-placeholder {
    width:clamp(44px,8vh,100px); height:clamp(44px,8vh,100px); border-radius:50%;
    background:linear-gradient(135deg,var(--accento) 0%,var(--tasto-da) 100%);
    display:flex; align-items:center; justify-content:center;
    font-size:clamp(20px,4vh,48px); font-weight:900; color:#fff;
    box-shadow:0 0 20px rgba(66,165,245,.4);
}
.header-center { flex:1 1 auto; text-align:center; padding:0 12px; }
.header-nome {
    font-size:clamp(18px,3.5vw,52px); font-weight:800; letter-spacing:.02em;
    color:var(--header-testo); text-shadow:0 2px 12px rgba(0,0,0,.5); line-height:1.1;
}
.header-sottotitolo {
    font-size:clamp(11px,1.5vw,22px); color:var(--accento);
    margin-top:4px; font-weight:400; letter-spacing:.03em; opacity:.9;
}
.header-clock { flex:0 0 auto; text-align:right; min-width:clamp(90px,12vw,200px); }
.clock-time {
    font-size:clamp(24px,4vw,64px); font-weight:700;
    font-variant-numeric:tabular-nums; color:var(--header-testo);
    line-height:1; letter-spacing:.05em; text-shadow:0 2px 12px rgba(0,0,0,.4);
}
.clock-date { font-size:clamp(10px,1.2vw,18px); color:var(--accento); margin-top:4px; letter-spacing:.04em; font-weight:500; }
.header-accent-bar { height:3px; background:linear-gradient(90deg,transparent,var(--accento),transparent); flex:0 0 3px; opacity:.7; }

/* BODY */
.kiosk-body {
    flex:1 1 auto; display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    padding:clamp(16px,3vh,48px) clamp(16px,4vw,80px); overflow:hidden;
}
.btn-grid {
    display:grid;
    grid-template-columns:repeat(var(--colonne),1fr);
    gap:clamp(12px,2.5vw,36px);
    width:100%;
    max-width:calc(var(--colonne) * clamp(180px,28vw,380px) + (var(--colonne) - 1) * 36px);
}
/* 1 colonna: grid wide + bottoni orizzontali */
.btn-grid.col1 {
    max-width:80%;
    width:80%;
}
.btn-grid.col1 .btn-servizio {
    flex-direction:row;
    justify-content:flex-start;
    min-height:clamp(70px,11vh,140px);
    padding:clamp(12px,2vh,28px) clamp(20px,4vw,56px);
    gap:clamp(12px,2.5vw,40px);
}
.btn-grid.col1 .btn-turno-block {
    flex-direction:row; align-items:center; gap:clamp(12px,2.5vw,28px);
    width:auto; flex:1 1 auto;
}
.btn-grid.col1 .btn-separatore {
    width:2px; height:clamp(40px,7vh,72px); margin:0;
    background:linear-gradient(180deg,transparent,rgba(255,255,255,.5),transparent);
}
.btn-grid.col1 .btn-lettera {
    flex:0 0 auto;
    font-size:clamp(28px,5vw,72px);
}
.btn-grid.col1 .btn-icona {
    flex:0 0 auto;
    font-size:clamp(28px,4.5vw,60px);
}
.btn-grid.col1 .btn-nome {
    text-align:left;
    font-size:clamp(18px,3.2vw,48px);
    flex:1 1 auto;
}
.btn-grid.col1 .btn-hint {
    display:none;
}
.btn-servizio {
    position:relative; display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    gap:clamp(8px,1.5vh,20px); padding:clamp(16px,3vh,40px) clamp(12px,2vw,32px);
    min-height:clamp(110px,18vh,260px); cursor:pointer;
    border:2px solid var(--tasto-bordo); border-radius:clamp(12px,2vw,24px);
    background:linear-gradient(145deg,var(--tasto-da) 0%,var(--tasto-a) 100%);
    color:var(--tasto-testo); font-family:inherit; overflow:hidden; outline:none;
    transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease,background .18s ease;
    box-shadow:0 8px 32px rgba(0,0,0,.35),inset 0 1px 0 rgba(255,255,255,.12);
}
.btn-servizio::before {
    content:''; position:absolute; inset:0; pointer-events:none; border-radius:inherit;
    background:linear-gradient(135deg,rgba(255,255,255,.13) 0%,rgba(255,255,255,.04) 40%,transparent 60%);
}
.btn-servizio::after {
    content:''; position:absolute; inset:-2px; border-radius:inherit; z-index:-1;
    background:radial-gradient(ellipse at 50% 0%,var(--accento) 0%,transparent 70%);
    opacity:0; transition:opacity .25s ease; pointer-events:none;
}
.btn-servizio:hover,.btn-servizio:focus-visible {
    transform:translateY(-4px) scale(1.025);
    border-color:var(--accento);
    background:linear-gradient(145deg,var(--tasto-hover-da) 0%,var(--tasto-hover-a) 100%);
    box-shadow:0 16px 48px rgba(0,0,0,.45),0 0 24px rgba(66,165,245,.3),inset 0 1px 0 rgba(255,255,255,.18);
}
.btn-servizio:hover::after { opacity:.6; }
.btn-servizio:active { transform:translateY(1px) scale(.98); box-shadow:0 4px 16px rgba(0,0,0,.4); transition-duration:.08s; }
.btn-servizio:disabled { opacity:.45; cursor:not-allowed; transform:none !important; filter:grayscale(.5); }
.btn-icona { font-size:clamp(24px,4vw,58px); line-height:1; filter:drop-shadow(0 2px 6px rgba(0,0,0,.4)); transition:transform .2s ease; }
.btn-servizio:hover .btn-icona { transform:scale(1.12) rotate(-3deg); }
.btn-turno-block {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:clamp(2px,0.4vh,6px); width:100%;
}
.btn-separatore {
    display:block; width:clamp(40px,6vw,80px); height:2px; flex-shrink:0;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,.5),transparent);
    margin:clamp(4px,0.6vh,10px) 0;
    border-radius:2px;
}
.btn-lettera {
    font-size:clamp(20px,3.2vw,48px); font-weight:900; letter-spacing:-.02em;
    line-height:1; text-shadow:0 2px 12px rgba(0,0,0,.45); opacity:.88;
}
.btn-nome {
    font-size:clamp(22px,4vw,64px); font-weight:800; letter-spacing:.14em;
    text-transform:uppercase; text-align:center; line-height:1.1;
    color:var(--accento);
    text-shadow:0 0 22px rgba(66,165,245,.6), 0 2px 10px rgba(0,0,0,.55);
    padding:clamp(8px,1.2vh,18px) clamp(16px,2.8vw,36px);
    background:rgba(255,255,255,.11);
    border:2px solid rgba(66,165,245,.5);
    border-radius:clamp(10px,1.5vw,20px);
    box-shadow:0 4px 24px rgba(0,0,0,.28), inset 0 1px 0 rgba(255,255,255,.18), 0 0 28px rgba(66,165,245,.2);
    word-break:break-word; opacity:1; max-width:100%;
}
.btn-hint { font-size:clamp(9px,1vw,13px); opacity:.5; font-weight:400; letter-spacing:.05em; text-transform:uppercase; }
.btn-servizio.loading .btn-icona { animation:spin .7s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }

/* STATO CHIUSO (fuori orario operativo) */
.btn-servizio.btn-chiuso {
    background:linear-gradient(145deg,var(--tasto-chiuso-da) 0%,var(--tasto-chiuso-a) 100%);
    border-color:rgba(255,255,255,.10);
    box-shadow:0 6px 22px rgba(0,0,0,.30),inset 0 1px 0 rgba(255,255,255,.06);
    filter:grayscale(.3); opacity:.82;
}
.btn-servizio.btn-chiuso::after { opacity:0 !important; }
.btn-servizio.btn-chiuso:hover,.btn-servizio.btn-chiuso:focus-visible {
    transform:none;
    background:linear-gradient(145deg,var(--tasto-chiuso-da) 0%,var(--tasto-chiuso-a) 100%);
    border-color:rgba(255,255,255,.14);
    box-shadow:0 6px 22px rgba(0,0,0,.30),inset 0 1px 0 rgba(255,255,255,.06);
}
.btn-servizio.btn-chiuso:hover .btn-icona { transform:none; }
.btn-servizio.btn-chiuso .btn-icona { filter:grayscale(1) drop-shadow(0 2px 6px rgba(0,0,0,.4)); }
.btn-stato {
    display:inline-flex; align-items:center; gap:6px;
    margin-top:6px; padding:4px 14px; border-radius:999px;
    background:rgba(0,0,0,.30); border:1px solid rgba(255,255,255,.20);
    font-size:clamp(9px,1.05vw,14px); font-weight:700; letter-spacing:.03em;
    line-height:1.2; color:#fff; opacity:.95; text-align:center;
}
.btn-stato::before { content:'\23F0'; font-size:1.05em; }
.btn-grid.col1 .btn-stato { margin-top:0; }

/* FOOTER */
.kiosk-footer {
    flex:0 0 auto; text-align:center;
    padding:clamp(6px,1.5vh,20px); border-top:1px solid rgba(255,255,255,.07);
    font-size:clamp(10px,1.1vw,16px); color:rgba(255,255,255,.3); letter-spacing:.08em;
}

/* ANIMAZIONI INGRESSO */
<?php if ($ui['animazioni_abilitate']): ?>
.kiosk-header { animation:slideDown .5s ease both; }
@keyframes slideDown { from { transform:translateY(-30px);opacity:0; } to { transform:none;opacity:1; } }
.btn-servizio { animation:fadeUp .45s ease both; }
<?php for ($i=0;$i<count($turni);$i++): ?>
.btn-grid .btn-servizio:nth-child(<?php echo $i+1; ?>) { animation-delay:<?php echo number_format(.15+$i*.08,2,'.',''); ?>s; }
<?php endfor; ?>
@keyframes fadeUp { from { transform:translateY(40px);opacity:0; } to { transform:none;opacity:1; } }
<?php endif; ?>

/* MODALI */
.modal-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.72); backdrop-filter:blur(7px);
    z-index:9999; justify-content:center; align-items:center;
}
.modal-overlay.show { display:flex; }
.modal-box {
    background:linear-gradient(135deg,var(--header-da),var(--header-a));
    border:1.5px solid rgba(255,255,255,.15); border-radius:clamp(16px,2.5vw,32px);
    padding:clamp(28px,5vh,60px) clamp(28px,5vw,60px);
    max-width:min(640px,90vw); width:90%; text-align:center;
    box-shadow:0 24px 80px rgba(0,0,0,.6),0 0 40px rgba(66,165,245,.15);
    animation:modalIn .3s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes modalIn { from { transform:scale(.8) translateY(20px);opacity:0; } to { transform:none;opacity:1; } }
.modal-box.success-box { border-color:rgba(67,200,120,.4); box-shadow:0 24px 80px rgba(0,0,0,.6),0 0 40px rgba(67,200,120,.2); }
.modal-icona { font-size:clamp(52px,8vw,96px); margin-bottom:16px; line-height:1; }
.modal-titolo { font-size:clamp(20px,3vw,40px); font-weight:800; margin-bottom:12px; }
.modal-msg { font-size:clamp(14px,2vw,26px); line-height:1.5; opacity:.85; }
.modal-numero { font-size:clamp(48px,8vw,110px); font-weight:900; color:var(--accento); margin:16px 0; line-height:1; text-shadow:0 0 30px rgba(66,165,245,.5); }
.modal-turno { font-size:clamp(16px,2.2vw,32px); font-weight:700; opacity:.75; margin-bottom:8px; }
.modal-timer { font-size:clamp(12px,1.4vw,18px); opacity:.5; margin-top:20px; }
.orari-box { background:rgba(255,255,255,.07); border-radius:12px; padding:16px; margin-top:18px; }
.orari-titolo { font-size:clamp(13px,1.5vw,20px); font-weight:700; margin-bottom:10px; color:var(--accento); }
.orari-item { font-size:clamp(12px,1.4vw,18px); padding:6px 0; opacity:.85; border-bottom:1px solid rgba(255,255,255,.07); }
.orari-item:last-child { border:none; }

@media (max-width:600px) { :root { --colonne:1; } }
@media (min-width:601px) and (max-width:900px) { :root { --colonne:min(2,var(--colonne)); } }
</style>
</head>
<body>
<div class="kiosk-wrap">

<!-- HEADER -->
<header class="kiosk-header">
  <div class="header-logo">
    <?php if ($logoExist): ?>
      <img src="<?php echo htmlspecialchars($ui['logo_path']); ?>?v=<?php echo filemtime($logoFile); ?>"
           alt="<?php echo htmlspecialchars($ui['nome_struttura']); ?>">
    <?php else: ?>
      <div class="header-logo-placeholder"><?php echo mb_strtoupper(mb_substr(trim($ui['nome_struttura']),0,1)); ?></div>
    <?php endif; ?>
  </div>
  <div class="header-center">
    <div class="header-nome"><?php echo htmlspecialchars($ui['nome_struttura']); ?></div>
    <?php if ($ui['sottotitolo'] !== ''): ?>
    <div class="header-sottotitolo"><?php echo htmlspecialchars($ui['sottotitolo']); ?></div>
    <?php endif; ?>
  </div>
  <div class="header-clock">
    <div class="clock-time" id="clockTime">--:--</div>
    <div class="clock-date" id="clockDate">---</div>
  </div>
</header>
<div class="header-accent-bar"></div>

<!-- GRIGLIA PULSANTI -->
<main class="kiosk-body">
<?php if (empty($turni)): ?>
  <div style="text-align:center;opacity:.5;font-size:clamp(16px,2vw,28px);padding:40px;">
    ⚠️ Nessun turno configurato nel database.
  </div>
<?php else: ?>
  <div class="btn-grid<?php echo $colonne===1?' col1':''; ?>"
       style="--colonne:<?php echo $colonne; ?>">
    <?php
    // ── Mappa icone CUP ospedaliero ──────────────────────────────
    // Le chiavi sono sottostringhe (minuscolo) del nome del turno.
    // L'ordine è importante: le voci più specifiche vanno prima.
    $iconeMap = [
        // ── PRENOTAZIONE / ACCETTAZIONE / CASSA ──────────────────
        'accett'    => '📋',  // Accettazione
        'prenotaz'  => '📅',  // Prenotazione
        'cassa'     => '💳',  // Cassa / Pagamento
        'pagam'     => '💳',  // Pagamento ticket
        'ticket'    => '🧾',  // Ticket sanitario
        'rimborso'  => '💰',  // Rimborsi
        'ammin'     => '🗂️', // Amministrazione / CUP
        'cup'       => '📅',  // CUP
        'sportell'  => '🏧',  // Sportello generico
        'info'      => 'ℹ️',  // Informazioni

        // ── URGENZA / PRONTO SOCCORSO ─────────────────────────────
        'pront'     => '🚨',  // Pronto Soccorso
        'urgenz'    => '🚨',  // Urgenza
        'emergenz'  => '🚨',  // Emergenza
        'triage'    => '🚦',  // Triage
        'trauma'    => '🦺',  // Trauma / Traumatologia

        // ── VISITE SPECIALISTICHE ─────────────────────────────────
        'cardiol'   => '🫀',  // Cardiologia
        'card'      => '🫀',  // Cardiologia (abbreviazione)
        'pneumol'   => '🫁',  // Pneumologia
        'pulmo'     => '🫁',  // Pneumologia
        'neurolog'  => '🧠',  // Neurologia
        'neuro'     => '🧠',  // Neurologia
        'psich'     => '🧠',  // Psichiatria
        'psicol'    => '🧠',  // Psicologia
        'oncol'     => '🎗️', // Oncologia
        'onco'      => '🎗️', // Oncologia
        'endocrin'  => '⚗️',  // Endocrinologia
        'diabet'    => '📊',  // Diabetologia
        'nefrol'    => '🫘',  // Nefrologia
        'urol'      => '🫘',  // Urologia
        'gastro'    => '🟡',  // Gastroenterologia
        'epatol'    => '🟡',  // Epatologia
        'reuma'     => '🦴',  // Reumatologia
        'ortop'     => '🦴',  // Ortopedia
        'ort'       => '🦷',  // Ortognatodonzia / Odontoiatria
        'odontoiat' => '🦷',  // Odontoiatria
        'dent'      => '🦷',  // Dentista
        'dermat'    => '🩹',  // Dermatologia
        'derm'      => '🩹',  // Dermatologia
        'oftalm'    => '👁️', // Oculistica / Oftalmologia
        'ocul'      => '👁️', // Oculistica
        'otorin'    => '👂',  // Otorinolaringoiatria
        'oto'       => '👂',  // ORL
        'ginecol'   => '🤱',  // Ginecologia
        'gin'       => '🤱',  // Ginecologia
        'ostetric'  => '🤱',  // Ostetricia
        'pediatr'   => '👶',  // Pediatria
        'ped'       => '👶',  // Pediatria
        'geriat'    => '🧓',  // Geriatria
        'androl'    => '🩺',  // Andrologia
        'fisiatr'   => '🏃',  // Fisiatria / Medicina fisica
        'fisiot'    => '🏃',  // Fisioterapia
        'riabil'    => '🏃',  // Riabilitazione
        'nutriz'    => '🥗',  // Nutrizione / Dietologia
        'dieto'     => '🥗',  // Dietologia
        'allergol'  => '🌿',  // Allergologia
        'immunol'   => '🛡️', // Immunologia
        'infettiv'  => '🦠',  // Malattie infettive
        'malatt'    => '🦠',  // Malattie infettive
        'angiolog'  => '🩸',  // Angiologia / Vascolare
        'vascol'    => '🩸',  // Chirurgia vascolare
        'chirur'    => '⚕️',  // Chirurgia generica
        'anest'     => '💊',  // Anestesia / Terapia del dolore
        'dolor'     => '💊',  // Terapia del dolore
        'medic'     => '🩺',  // Medicina interna / generica
        'intern'    => '🩺',  // Medicina interna
        'sport'     => '⚽',  // Medicina dello sport
        'lavor'     => '⚒️',  // Medicina del lavoro

        // ── DIAGNOSTICA ───────────────────────────────────────────
        'radiolog'  => '🩻',  // Radiologia
        'radio'     => '🩻',  // Radiologia
        'tac'       => '🩻',  // TC / TAC
        'risonan'   => '🩻',  // Risonanza magnetica
        'mri'       => '🩻',  // MRI
        'ecograf'   => '〰️', // Ecografia
        'eco'       => '〰️', // Ecografia
        'mammog'    => '🩻',  // Mammografia
        'moc'       => '🦴',  // MOC / Densitometria ossea
        'eeg'       => '🧠',  // EEG
        'emg'       => '⚡',  // EMG
        'ecg'       => '📈',  // ECG / Holter / Elettrocardiogramma
        'elettroc'  => '📈',  // Elettrocardiogramma
        'holter'    => '📈',  // Holter
        'spirom'    => '🫁',  // Spirometria
        'endosc'    => '🔭',  // Endoscopia
        'colons'    => '🔭',  // Colonscopia
        'gastros'   => '🔭',  // Gastroscopia
        'biopsi'    => '🔬',  // Biopsia
        'anatomop'  => '🔬',  // Anatomia Patologica

        // ── LABORATORIO / PRELIEVI ────────────────────────────────
        'laborat'   => '🧪',  // Laboratorio analisi
        'analisi'   => '🧪',  // Analisi
        'preliev'   => '💉',  // Prelievi ematici
        'ematol'    => '🩸',  // Ematologia
        'trasf'     => '🩸',  // Trasfusionale
        'microb'    => '🦠',  // Microbiologia
        'sierol'    => '🧫',  // Sierologia
        'citol'     => '🔬',  // Citologia
        'urin'      => '🧫',  // Esame urine

        // ── VACCINAZIONI / MEDICINA PREVENTIVA ───────────────────
        'vaccin'    => '💉',  // Vaccinazione
        'vaccaz'    => '💉',  // Vaccinazione
        'screeni'   => '🔍',  // Screening
        'prevenz'   => '🛡️', // Medicina preventiva
        'igiene'    => '🛡️', // Igiene pubblica

        // ── DAY HOSPITAL / RICOVERO ────────────────────────────────
        'day hosp'  => '🛏️', // Day Hospital
        'day'       => '🛏️', // Day Hospital / Day Surgery
        'ricov'     => '🛏️', // Ricovero
        'degenz'    => '🛏️', // Degenza

        // ── REFERTAZIONE / DOCUMENTAZIONE ────────────────────────
        'refert'    => '📄',  // Ritiro referti
        'ritiro'    => '📄',  // Ritiro documentazione
        'cartell'   => '📁',  // Cartella clinica
        'docum'     => '📁',  // Documentazione sanitaria
        'certif'    => '📋',  // Certificati
        'prescriz'  => '📝',  // Prescrizioni
        'presc'     => '📝',  // Prescrizioni
        'impegn'    => '📝',  // Impegnativa
        'esenzi'    => '📝',  // Esenzioni ticket

        // ── FARMACIA / PROTESICA ──────────────────────────────────
        'farmac'    => '💊',  // Farmacia
        'protet'    => '♿',  // Protesica / Ausili
        'ausil'     => '♿',  // Ausili
        'invalidi'  => '♿',  // Invalidità
        'disab'     => '♿',  // Disabilità

        // ── GENERICI ─────────────────────────────────────────────
        'ospedal'   => '🏥',  // Ospedale
        'clinic'    => '🏥',  // Clinica
        'poliamb'   => '🏥',  // Poliambulatorio
        'dott'      => '👨‍⚕️', // Dottore
        'spec'      => '🩺',  // Specialista
    ];
    // Icone predefinite a tema ospedaliero per turni senza corrispondenza
    $iconeDefault = ['🩺','➕','🏥','💊','🩸','🧪','📋','🩻','👨‍⚕️','🛡️'];
    $idxD=0;
    foreach($turni as $t):
      $lettera = $t['nometurno'];            // A, B, C, D …
      $desc    = $t['descservizio'] ?? '';   // ATTIVITA' SSN, LABORATORIO ANALISI …
      // Usa la descrizione del servizio per abbinare l'icona
      $matchStr = mb_strtolower($desc !== '' ? $desc : $lettera);
      $icona = $iconeDefault[$idxD % count($iconeDefault)];
      foreach($iconeMap as $k=>$ico){ if(strpos($matchStr,$k)!==false){$icona=$ico;break;} }
      $idxD++;
      $ariaLabel = $lettera . ($desc !== '' ? ' – ' . $desc : '');
      $orariSett = $orariTurniSett[(string)$t['idturno']] ?? new stdClass();
    ?>
    <button class="btn-servizio"
            data-idturno="<?php echo intval($t['idturno']); ?>"
            data-nome="<?php echo htmlspecialchars($lettera,ENT_QUOTES); ?>"
            data-orari='<?php echo htmlspecialchars(json_encode($orariSett), ENT_QUOTES); ?>'
            aria-label="Servizio <?php echo htmlspecialchars($ariaLabel,ENT_QUOTES); ?>">
      <?php if($ui['icone_abilitate']): ?>
      <span class="btn-icona"><?php echo $icona; ?></span>
      <?php endif; ?>
      <div class="btn-turno-block">
        <span class="btn-lettera"><?php echo htmlspecialchars($lettera); ?></span>
        <?php if($desc !== ''): ?>
        <span class="btn-separatore" aria-hidden="true"></span>
        <span class="btn-nome"><?php echo htmlspecialchars($desc); ?></span>
        <?php endif; ?>
      </div>
      <span class="btn-hint">Premi per il numero</span>
      <span class="btn-stato" style="display:none"></span>
    </button>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
</main>

<?php if($ui['mostra_footer'] && $ui['footer_testo']!==''): ?>
<footer class="kiosk-footer"><?php echo htmlspecialchars($ui['footer_testo']); ?></footer>
<?php endif; ?>

<!-- MODALE ERRORE -->
<div id="modalErrore" class="modal-overlay" role="dialog" aria-modal="true">
  <div class="modal-box">
    <div class="modal-icona">⏰</div>
    <div class="modal-titolo" id="errTitolo">Servizio Chiuso</div>
    <div class="modal-msg" id="errMsg"></div>
    <div id="errOrari" class="orari-box" style="display:none">
      <div class="orari-titolo">Orari di apertura</div>
      <div id="errOrariList"></div>
    </div>
    <div class="modal-timer" id="errTimer"></div>
  </div>
</div>

<!-- MODALE SUCCESSO -->
<div id="modalSuccesso" class="modal-overlay" role="dialog" aria-modal="true">
  <div class="modal-box success-box">
    <div class="modal-icona">✅</div>
    <div class="modal-titolo">Biglietto emesso!</div>
    <div class="modal-turno" id="succTurno"></div>
    <div class="modal-numero" id="succNumero"></div>
    <div class="modal-msg">Conservate questo biglietto e attendete il vostro numero.</div>
    <div class="modal-timer" id="succTimer"></div>
  </div>
</div>

<!-- MODALE STAMPA -->
<div id="modalStampa" class="modal-overlay" role="dialog" aria-modal="true">
  <div class="modal-box">
    <div class="modal-icona" style="animation:spin .9s linear infinite;display:inline-block">⚙️</div>
    <div class="modal-titolo" style="margin-top:12px">Stampa in corso…</div>
    <div class="modal-msg" style="opacity:.6">Attendere prego</div>
  </div>
</div>

</div><!-- /kiosk-wrap -->
<script src="jquery-3.6.0.min.js"></script>
<script>
// Orologio
(function(){
    var G=['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'];
    var M=['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    function tick(){
        var n=new Date();
        var h=String(n.getHours()).padStart(2,'0');
        var m=String(n.getMinutes()).padStart(2,'0');
        var s=String(n.getSeconds()).padStart(2,'0');
        document.getElementById('clockTime').textContent=h+':'+m+':'+s;
        document.getElementById('clockDate').textContent=G[n.getDay()]+' '+n.getDate()+' '+M[n.getMonth()]+' '+n.getFullYear();
    }
    tick(); setInterval(tick,1000);
})();

// ── Comportamento pulsanti fuori orario ───────────────────────
var SERVER_ORA_BASE = <?php echo (int)date('Hi'); ?>;
var SERVER_DAY = <?php echo (int)date('w'); ?>;
var PAGE_LOAD_MS = Date.now();
function _serverOraNum(){
    var elapsedMin=Math.floor((Date.now()-PAGE_LOAD_MS)/60000);
    var h=Math.floor(SERVER_ORA_BASE/100);
    var m=(SERVER_ORA_BASE%100)+elapsedMin;
    h+=Math.floor(m/60); m=m%60;
    return h*100+m;
}

var PULS_CFG = {
    attivo:      <?php echo $ui['pulsanti_stato_attivo'] ? 'true' : 'false'; ?>,
    modo:        <?php echo json_encode($ui['pulsanti_modo_chiuso']); ?>,
    mostraMsg:   <?php echo $ui['pulsanti_mostra_messaggio'] ? 'true' : 'false'; ?>,
    testoChiuso: <?php echo json_encode($ui['pulsanti_testo_chiuso']); ?>
};

function _oraNum(d){ return d.getHours()*100 + d.getMinutes(); }
function _fmtOra(v){ var s=String(v); while(s.length<4) s='0'+s; return s.substr(0,2)+':'+s.substr(2,2); }

function valutaStatoPulsanti(){
    if(!PULS_CFG.attivo) return;
    
    var cur = _serverOraNum();
    var giornoOggi = SERVER_DAY; // 0=Domenica ... 6=Sabato (come date('w') in PHP)
    document.querySelectorAll('.btn-servizio').forEach(function(btn){
        var settimana = {};
        try { settimana = JSON.parse(btn.getAttribute('data-orari') || '{}') || {}; } catch(e){ settimana = {}; }
        var orari = settimana[giornoOggi] || settimana[String(giornoOggi)] || [];
        var aperto = false, apreA = null;
        for(var k=0;k<orari.length;k++){
            var i = parseInt(orari[k].i,10), f = parseInt(orari[k].f,10);
            if(isNaN(i)||isNaN(f)) continue;
            if(cur>=i && cur<=f){ aperto = true; break; }
            if(cur<i && (apreA===null || i<apreA)) apreA = i;
        }
        var badge = btn.querySelector('.btn-stato');

        if(aperto){
            btn.classList.remove('btn-chiuso');
            btn.style.display = '';
            if(PULS_CFG.modo === 'disabilita') btn.disabled = false;
            if(badge) badge.style.display = 'none';
            btn.setAttribute('data-chiuso','0');
            return;
        }

        // Servizio chiuso
        btn.setAttribute('data-chiuso','1');
        if(PULS_CFG.modo === 'nascondi'){ btn.style.display = 'none'; return; }
        btn.style.display = '';
        btn.classList.add('btn-chiuso');
        if(PULS_CFG.modo === 'disabilita') btn.disabled = true;

        if(badge){
            if(PULS_CFG.mostraMsg){
                var txt = PULS_CFG.testoChiuso || 'Chiuso';
                if(apreA !== null) txt += ' · apre alle ' + _fmtOra(apreA);
                badge.textContent = txt;
                badge.style.display = 'inline-flex';
            } else {
                badge.style.display = 'none';
            }
        }
    });
}

// Countdown
function startCountdown(elId,sec,cb){
    var el=document.getElementById(elId);
    function u(s){el.textContent='Chiusura automatica tra '+s+' second'+(s===1?'o':'i')+'…';}
    u(sec);
    var iv=setInterval(function(){sec--;if(sec>0)u(sec);else{clearInterval(iv);if(cb)cb();}},1000);
    return iv;
}
var _errIv,_succIv;

function mostraModaleErrore(titolo,msg,orari){
    clearInterval(_errIv);
    document.getElementById('errTitolo').textContent=titolo||'Servizio non disponibile';
    document.getElementById('errMsg').textContent=msg;
    var ob=document.getElementById('errOrari'),ol=document.getElementById('errOrariList');
    if(orari&&orari.length>0){
        ol.innerHTML=orari.map(function(o){return '<div class="orari-item">'+o.giorno+': '+o.inizio+' – '+o.fine+'</div>';}).join('');
        ob.style.display='block';
    } else { ob.style.display='none'; }
    document.getElementById('modalErrore').classList.add('show');
    _errIv=startCountdown('errTimer',6,function(){document.getElementById('modalErrore').classList.remove('show');});
}
function mostraModaleSuccesso(turno,numero){
    clearInterval(_succIv);
    document.getElementById('succTurno').textContent=turno;
    document.getElementById('succNumero').textContent=numero;
    document.getElementById('modalSuccesso').classList.add('show');
    _succIv=startCountdown('succTimer',5,function(){document.getElementById('modalSuccesso').classList.remove('show');});
}
document.getElementById('modalErrore').addEventListener('click',function(e){
    if(e.target===this){clearInterval(_errIv);this.classList.remove('show');}
});

function stampaBiglietto(turno,numero,data){
    document.getElementById('modalStampa').classList.add('show');
    $.ajax({
        url:<?php echo $printEndpointJs; ?>,type:'POST',dataType:'json',
        data:{turno:turno,numero:numero,data:data},
        success:function(resp){
            document.getElementById('modalStampa').classList.remove('show');
            if(resp.success) mostraModaleSuccesso(turno,numero);
            else mostraModaleErrore('Errore stampante',resp.error||'Stampa non riuscita.',[]);
        },
        error:function(){
            document.getElementById('modalStampa').classList.remove('show');
            mostraModaleErrore('Errore comunicazione','Impossibile contattare il server di stampa.',[]);
        }
    });
}


// ── Aggiornamento silenzioso (polling) ────────────────────────
var TOTEM_POLL_MS = 180000;
var TOTEM_LAYOUT_VERSION = <?php
    $lv = (string)filemtime(__FILE__);
    $ucf = __DIR__ . '/totem_ui_config.json';
    if (file_exists($ucf)) $lv .= '_' . filemtime($ucf);
    echo json_encode($lv);
?>;
var _totemVersion = '';
var _totemPollBusy = false;

function _syncServerTime(ora, day) {
    SERVER_ORA_BASE = ora;
    SERVER_DAY = day;
    PAGE_LOAD_MS = Date.now();
}

function _isModalOpen() {
    return document.querySelector('.modal-overlay.show') !== null;
}

function _creaPulsanteServizio(t) {
    var btn = document.createElement('button');
    btn.className = 'btn-servizio';
    btn.setAttribute('data-idturno', t.idturno);
    btn.setAttribute('data-nome', t.nometurno);
    btn.setAttribute('data-orari', JSON.stringify(t.orari || {}));
    var aria = t.nometurno + (t.descservizio ? ' – ' + t.descservizio : '');
    btn.setAttribute('aria-label', 'Servizio ' + aria);
    if (t.icona) {
        var ic = document.createElement('span');
        ic.className = 'btn-icona';
        ic.textContent = t.icona;
        btn.appendChild(ic);
    }
    var block = document.createElement('div');
    block.className = 'btn-turno-block';
    var lt = document.createElement('span');
    lt.className = 'btn-lettera';
    lt.textContent = t.nometurno;
    block.appendChild(lt);
    if (t.descservizio) {
        var sep = document.createElement('span');
        sep.className = 'btn-separatore';
        sep.setAttribute('aria-hidden', 'true');
        block.appendChild(sep);
        var nm = document.createElement('span');
        nm.className = 'btn-nome';
        nm.textContent = t.descservizio;
        block.appendChild(nm);
    }
    btn.appendChild(block);
    var hint = document.createElement('span');
    hint.className = 'btn-hint';
    hint.textContent = 'Premi per il numero';
    btn.appendChild(hint);
    var st = document.createElement('span');
    st.className = 'btn-stato';
    st.style.display = 'none';
    btn.appendChild(st);
    return btn;
}

function applicaAggiornamentoTotem(data) {
    if (!data || !data.version) return;
    _syncServerTime(data.server_ora, data.server_day);

    var elNome = document.querySelector('.header-nome');
    if (elNome && data.ui) elNome.textContent = data.ui.nome_struttura || elNome.textContent;
    var elSotto = document.querySelector('.header-sottotitolo');
    if (data.ui && data.ui.sottotitolo) {
        if (elSotto) elSotto.textContent = data.ui.sottotitolo;
    }
    var footer = document.querySelector('.kiosk-footer');
    if (footer && data.ui) footer.textContent = data.ui.footer_testo || '';

    var grid = document.querySelector('.btn-grid');
    if (!grid || !data.turni) { valutaStatoPulsanti(); return; }

    var existingIds = Array.prototype.map.call(grid.querySelectorAll('.btn-servizio'), function(b) {
        return String(b.getAttribute('data-idturno'));
    });
    var newIds = data.turni.map(function(t) { return String(t.idturno); });
    var sameStructure = existingIds.length === newIds.length && existingIds.every(function(id, i) {
        return id === newIds[i];
    });

    if (sameStructure) {
        data.turni.forEach(function(t) {
            var btn = grid.querySelector('.btn-servizio[data-idturno="' + t.idturno + '"]');
            if (!btn) return;
            btn.setAttribute('data-orari', JSON.stringify(t.orari || {}));
            btn.setAttribute('data-nome', t.nometurno);
            var nome = btn.querySelector('.btn-nome');
            if (nome) nome.textContent = t.descservizio || '';
            var lettera = btn.querySelector('.btn-lettera');
            if (lettera) lettera.textContent = t.nometurno;
            if (t.icona) {
                var ic = btn.querySelector('.btn-icona');
                if (ic) ic.textContent = t.icona;
            }
        });
    } else {
        grid.innerHTML = '';
        data.turni.forEach(function(t) { grid.appendChild(_creaPulsanteServizio(t)); });
    }
    valutaStatoPulsanti();
}

function aggiornaTotemSilenzioso() {
    if (_totemPollBusy || _isModalOpen()) return;
    _totemPollBusy = true;
    fetch('totem_status.php', { cache: 'no-store', credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data || !data.version) return;
            if (data.layout_version && data.layout_version !== TOTEM_LAYOUT_VERSION) {
                location.reload();
                return;
            }
            if (_totemVersion === '') { _totemVersion = data.version; return; }
            if (data.version !== _totemVersion) {
                _totemVersion = data.version;
                applicaAggiornamentoTotem(data);
            } else {
                _syncServerTime(data.server_ora, data.server_day);
                valutaStatoPulsanti();
            }
        })
        .catch(function() { /* silenzioso */ })
        .finally(function() { _totemPollBusy = false; });
}

function avviaPollingTotem() {
    aggiornaTotemSilenzioso();
    setInterval(aggiornaTotemSilenzioso, TOTEM_POLL_MS);
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) aggiornaTotemSilenzioso();
    });
}



// Auto-reload quando cambiano orari/turni (dopo sync notturna). Sicuro: differisce se modale/stampa attivi.
var ORARI_VER = <?php echo json_encode($orariVer ?? '', JSON_UNESCAPED_UNICODE); ?>;
var _orariReloadPending = false;
function totemIsBusy(){
    return !!document.querySelector('.modal-overlay.show')
        || !!document.querySelector('.btn-servizio.loading');
}
function pollOrariVersion(){
    $.ajax({
        url:'totem.php',
        data:{orari_ver:1, _:Date.now()},
        dataType:'json',
        cache:false,
        timeout:8000,
        success:function(d){
            if(!d || !d.ok || !d.v) return;
            if(d.v === ORARI_VER) return;
            if(totemIsBusy()){ _orariReloadPending = true; return; }
            window.location.reload();
        }
        // errori di rete: ignorati (il totem continua a funzionare)
    });
}

$(document).ready(function(){
    valutaStatoPulsanti();
    setInterval(valutaStatoPulsanti,30000);
    avviaPollingTotem();

    // Prima verifica dopo 60s, poi ogni 90s. Se pending e non busy → reload.
    setTimeout(function(){
        pollOrariVersion();
        setInterval(function(){
            if(_orariReloadPending && !totemIsBusy()){ window.location.reload(); return; }
            pollOrariVersion();
        }, 90000);
    }, 60000);

    $(document).on('click','.btn-servizio',function(){
        var $b=$(this),id=$b.data('idturno');
        // Pulsante chiuso: se il messaggio è disattivato non fare nulla (silenzioso)
        if(PULS_CFG.attivo && PULS_CFG.modo!=='nascondi' && $b.attr('data-chiuso')==='1' && !PULS_CFG.mostraMsg){
            return;
        }
        $('.btn-servizio').prop('disabled',true);
        $b.addClass('loading');
        $.ajax({
            url:'prendinumero.php',type:'POST',dataType:'json',
            data:{idturno:id},
            success:function(r){
                $b.removeClass('loading');
                if(r.success) stampaBiglietto(r.turno,r.numero,r.data);
                else mostraModaleErrore('Servizio non disponibile',r.error||'Impossibile ottenere il numero.',r.orari||[]);
                setTimeout(function(){$('.btn-servizio').prop('disabled',false);valutaStatoPulsanti();},800);
            },
            error:function(){
                $b.removeClass('loading');
                mostraModaleErrore('Errore di rete','Comunicazione col server non riuscita. Riprovare.',[]);
                setTimeout(function(){$('.btn-servizio').prop('disabled',false);valutaStatoPulsanti();},800);
            }
        });
    });
});
</script>
</body>
</html>