<?php
/**
 * totem_status.php – Endpoint JSON leggero per aggiornamento silenzioso del totem.
 */
include 'connect.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$uiConfigFile = __DIR__ . '/totem_ui_config.json';
$ui = [];
if (file_exists($uiConfigFile)) {
    $ui = json_decode(file_get_contents($uiConfigFile), true) ?: [];
}
$ui = array_merge([
    'nome_struttura' => 'MySanitario',
    'sottotitolo'      => '',
    'footer_testo'     => '',
    'mostra_footer'    => true,
    'icone_abilitate'  => true,
    'colonne_max'      => 3,
], $ui);

$turni = [];
$sql_cfg = "SELECT ct.idturno, t.turno AS nometurno, t.desstato AS descservizio
            FROM configturnitotem ct
            INNER JOIN configtotem c ON c.idconfigtotem = ct.idconfigtotem
            INNER JOIN turni t ON t.ID_turno = ct.idturno
            ORDER BY ct.idturno";
$res_cfg = mysqli_query($conn, $sql_cfg);
if ($res_cfg && mysqli_num_rows($res_cfg) > 0) {
    while ($r = mysqli_fetch_assoc($res_cfg)) {
        $turni[] = ['idturno' => (int)$r['idturno'], 'nometurno' => $r['nometurno'], 'descservizio' => $r['descservizio']];
    }
} else {
    $res_all = mysqli_query($conn, "SELECT ID_turno AS idturno, turno AS nometurno, desstato AS descservizio FROM turni ORDER BY ID_turno");
    if ($res_all) {
        while ($r = mysqli_fetch_assoc($res_all)) {
            $turni[] = ['idturno' => (int)$r['idturno'], 'nometurno' => $r['nometurno'], 'descservizio' => $r['descservizio']];
        }
    }
}

$orariTurniSett = [];
$res_orari = mysqli_query($conn,
    "SELECT ot.id_turno, og.giorno, og.ora_inizio, og.ora_fine
     FROM operazioni_turni ot
     INNER JOIN operazioni_giorni og ON ot.id_operazione = og.id_operazione");
if ($res_orari) {
    while ($r = mysqli_fetch_assoc($res_orari)) {
        $ini = str_replace(':', '', (string)$r['ora_inizio']);
        $fin = str_replace(':', '', (string)$r['ora_fine']);
        if ($ini === '' || $fin === '') continue;
        $g = (string)(int)$r['giorno'];
        $orariTurniSett[(string)$r['id_turno']][$g][] = ['i' => $ini, 'f' => $fin];
    }
}
mysqli_close($conn);

function totem_resolve_icon(string $desc, string $lettera, int $idx): string {
    $iconeMap = [
        'cup'=>'📅','ammin'=>'🗂️','prenotaz'=>'📅','accett'=>'📋','cassa'=>'💳','anagrafe'=>'🗂️',
        'laborat'=>'🧪','analisi'=>'🧪','radiolog'=>'🩻','cardiol'=>'🫀','pediatr'=>'👶',
        'ginecol'=>'🤱','ortop'=>'🦴','dermat'=>'🩹','oftalm'=>'👁️','otorin'=>'👂',
    ];
    $iconeDefault = ['🩺','➕','🏥','💊','🩸','🧪','📋','🩻','👨‍⚕️','🛡️'];
    $matchStr = mb_strtolower($desc !== '' ? $desc : $lettera);
    foreach ($iconeMap as $k => $ico) {
        if (strpos($matchStr, $k) !== false) return $ico;
    }
    return $iconeDefault[$idx % count($iconeDefault)];
}

$outTurni = [];
$idx = 0;
foreach ($turni as $t) {
    $id = (string)$t['idturno'];
    $outTurni[] = [
        'idturno'      => $t['idturno'],
        'nometurno'    => $t['nometurno'],
        'descservizio' => $t['descservizio'],
        'orari'        => $orariTurniSett[$id] ?? new stdClass(),
        'icona'        => !empty($ui['icone_abilitate']) ? totem_resolve_icon($t['descservizio'] ?? '', $t['nometurno'], $idx) : '',
    ];
    $idx++;
}

$layoutVersion = (string)filemtime(__DIR__ . '/totem.php');
if (file_exists($uiConfigFile)) {
    $layoutVersion .= '_' . filemtime($uiConfigFile);
}

$payload = [
    'server_ora' => (int)date('Hi'),
    'server_day' => (int)date('w'),
    'ui' => [
        'nome_struttura' => $ui['nome_struttura'],
        'sottotitolo'    => $ui['sottotitolo'] ?? '',
        'footer_testo'   => $ui['footer_testo'] ?? '',
        'mostra_footer'  => !empty($ui['mostra_footer']),
    ],
    'colonne' => max(1, min((int)($ui['colonne_max'] ?? 3), max(1, count($outTurni)))),
    'turni'          => $outTurni,
    'layout_version' => $layoutVersion,
];
$payload['version'] = md5(json_encode($payload));

echo json_encode($payload);
