<?php

include 'connect.php';

function getClientIP() {
    return $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        // IP condiviso da proxy
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // IP passato da proxy
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        // IP remoto diretto
        return $_SERVER['REMOTE_ADDR'];
    }
}

$ipclient = getClientIP();
//echo "ipclient = ".$ipclient."<br>";
$postazione = $_GET["postazione"] ?? 1;

$sql = "SELECT * FROM configmonitor";
$result = mysqli_query($conn, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    $idconfigmonitor = $row['idconfigmonitor'];
    $img = $row['immaginesfondo'];
    $nome_file = basename($img);
    $estensione = pathinfo($nome_file, PATHINFO_EXTENSION);
    $img_url = "immagini/sfondomonitor." . $estensione; // <-- il tuo path noto
    $ximmaginesfondo = $row['ximmaginesfondo'];
    $yimmaginesfondo = $row['yimmaginesfondo'];

    $box_news = $row["boxnews"];
    if ($box_news == '1') {
        $box_news_left_db = $row["xboxnews"];
        $box_news_top_db = $row["yboxnews"];
        $box_news_width_db = $row["wboxnews"];
        $box_news_height_db = $row["hboxnews"];
        $box_news_url = $row["urlboxnews"];
    }

    $box_immagini = $row["boximmagini"];
    if ($box_immagini == '1') {
        $box_immagini_left_db = $row["xboximmagini"];
        $box_immagini_top_db = $row["yboximmagini"];
        $box_immagini_width_db = $row["wboximmagini"];
        $box_immagini_height_db = $row["hboximmagini"];
    }

    $box_video = $row["boxvideo"];
    if ($box_video == '1') {
        $box_video_left_db = $row["xboxvideo"];
        $box_video_top_db = $row["yboxvideo"];
        $box_video_width_db = $row["wboxvideo"];
        $box_video_height_db = $row["hboxvideo"];
    }

    $box_chiamati = $row["boxchiamati"];
    if ($box_chiamati == '1') {
        $box_chiamati_left_db = $row["xboxchiamati"];
        $box_chiamati_top_db = $row["yboxchiamati"];
        $box_chiamati_width_db = $row["wboxchiamati"];
        $box_chiamati_height_db = $row["hboxchiamati"];
        $box_chiamati_font = $row["fontboxchiamati"];
        $box_chiamati_bold = $row["boldboxchiamati"];
    }

    // Seconda query
    $sql = "SELECT * FROM configturnimonitor";
    $rsturni = mysqli_query($conn, $sql);
    $turni = [];
    $contatori = [];
    $postazioni = [];
    while ($rowturno = mysqli_fetch_assoc($rsturni)) {
        $sql_turni = "SELECT * FROM turni WHERE ID_turno = " . intval($rowturno['idturno']); // sicurezza
        $rsturno = mysqli_query($conn, $sql_turni);
        $nometurno = '';
        if ($rowturno1 = mysqli_fetch_assoc($rsturno)) {
            $nometurno = $rowturno1['turno'];
        }
        // TURNI
        $turno_tmp = [
            "idturno" => $rowturno['idturno'],
            "fontturno" => $rowturno['fontturno'],
            "coloreturno" => $rowturno['coloreturno'],
            "sizeturno" => $rowturno['sizeturno'],
            "boldturno" => $rowturno['boldturno'],
            "xturno" => $rowturno['xturno'],
            "yturno" => $rowturno['yturno'],
            "wturno" => $rowturno['wturno'],
            "hturno" => $rowturno['hturno'],
            "nometurno" => $nometurno
        ];
        $turni[] = $turno_tmp;

        // CONTATORI
        $contatore_tmp = [
            "idturno" => $rowturno['idturno'],
            "xcontatore" => $rowturno['xcontatore'],
            "ycontatore" => $rowturno['ycontatore'],
            "wcontatore" => $rowturno['wcontatore'],
            "hcontatore" => $rowturno['hcontatore'],
            "fontcontatore" => $rowturno['fontcontatore'],
            "colorecontatore" => $rowturno['colorecontatore'],
            "sizecontatore" => $rowturno['sizecontatore'],
            "boldcontatore" => $rowturno['boldcontatore']
        ];
        $contatori[] = $contatore_tmp;

        // POSTAZIONI
        $postazione_tmp = [
            "idturno" => $rowturno['idturno'],
            "xpostazione" => $rowturno['xpostazione'],
            "ypostazione" => $rowturno['ypostazione'],
            "wpostazione" => $rowturno['wpostazione'],
            "hpostazione" => $rowturno['hpostazione'],
            "fontpostazione" => $rowturno['fontpostazione'],
            "colorepostazione" => $rowturno['colorepostazione'],
            "sizepostazione" => $rowturno['sizepostazione'],
            "boldpostazione" => $rowturno['boldpostazione']
        ];
        $postazioni[] = $postazione_tmp;
    }
} else {
    $img = '';
    $ximmaginesfondo = $yimmaginesfondo = 0;
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Impostazioni sfondo</title>
    <style>
        body {
            background-image: url('img/laboratorio-analisi-roma.png');
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: center center;
            background-attachment: fixed;
        }
    </style>
</head>
<body>
<!--button onclick="playOne();" id="buttonplay">Play</button-->
<audio id="audioPlayer"></audio>

<?php 
    $font_size_chiamati = 30; // Dimensione del font per il box chiamati
?>

    <div id="boxchiamati"
     data-left="20"
     data-top="140"
     data-width="300"
     data-height="300"
     data-base-width="200"
     data-base-height="300"
     style="position:absolute; 
        display: flex;
        justify-content: center;
        align-items: flex-start;
        font-family: Impact, sans-serif;
        font-size: 30px;
        font-weight: bold;">
        <span id="boxchiamati_text"></span>
    </div>

    <div id="boxnumero"
     data-left="350"
     data-top="110"
     data-width="600"
     data-height="300"
     data-base-width="200"
     data-base-height="300"
     style="position:absolute; 
        display: flex;
        justify-content: center;
        align-items: flex-start;
        font-family: Impact, sans-serif;
        font-size: 180px;
        font-weight: normal;">
        <span id="boxnumero_text">-</span>
    </div>

<script src="jquery-3.6.0.min.js"></script> 
<script>
function posizionaBox(box) {
    var box = document.getElementById(box);
    var baseWidth = parseFloat(box.dataset.baseWidth);
    var baseHeight = parseFloat(box.dataset.baseHeight);
    var boxLeft = parseFloat(box.dataset.left);
    var boxTop = parseFloat(box.dataset.top);
    var boxWidth = parseFloat(box.dataset.width);
    var boxHeight = parseFloat(box.dataset.height);

    // Calcola i rapporti di scala rispetto alla finestra attuale
    var scalaX = window.innerWidth / baseWidth;
    var scalaY = window.innerHeight / baseHeight;

    // Applica le nuove dimensioni e posizione
    /*box.style.left = (boxLeft * scalaX) + "px";
    box.style.top = (boxTop * scalaY) + "px";
    box.style.width = (boxWidth * scalaX) + "px";
    box.style.height = (boxHeight * scalaY) + "px";*/
    box.style.left = (boxLeft) + "px";
    box.style.top = (boxTop) + "px";
    box.style.width = (boxWidth) + "px";
    box.style.height = (boxHeight) + "px";
}

// Chiama la funzione al caricamento e al resize
window.addEventListener('load', function() {
    posizionaBox('boxchiamati');
    posizionaBox('boxnumero');
});

</script>
<script>
    let vChiamati = [];
    vChiamati.push(' ');
    vChiamati.push(' ');
    vChiamati.push(' ');
    vChiamati.push(' ');
    vChiamati.push(' ');
    let busy = false; // Variabile per gestire lo stato di busy
    function chiamanumero() {
		if (busy) console.log('Occupato');
        if (busy) return;
        busy = true; // Imposta busy a true per evitare chiamate multiple
        $.ajax({
            url: 'chiamanumero_ambulatorio.php',
            type: 'POST',
            data: { action: 'chiamanumero', ipmonitor: '<?php echo $ipclient; ?>', postazione: '<?php echo $postazione; ?>'},
            success: function(response) {
                if (response != '') {
                    let parts = response.split('|');
                    sTurno = parts[0];
                    sNumero = parseInt(parts[1]).toString();
                    let audioFiles = [
                        `mp3/Turno ${sTurno}.mp3`,
                        `mp3/${sNumero}.mp3`
                    ];
                    let sportello = parts[2];
                    let idturno = parts[4];
                    let iddivpostazione = '#boxpostazione_' + idturno;
                    let iddivcontatore = '#boxcontatore_' + idturno;
                    $(iddivpostazione).text(sportello);
                    $(iddivcontatore).text(parts[1]);
                    playSequence(audioFiles, function() {
                        busy = false; // Rilascia lo stato di busy dopo la sequenza
                        setTimeout(() => {
                            chiamanumero();
                        }, 1000);
                    });
                    for (i = 4; i > 0; i--) {
						vChiamati[i] = vChiamati[i - 1];
					}
                    vChiamati[0] = parts[0] + '-' + parts[1] + '(' + parts[5] + ')';// + ' sportello ' + parts[2];
                    Testo = '';
					for (i=0; i<5; i++) {
						Testo = Testo + vChiamati[i] + '<br>';
					}
					$('#boxchiamati_text').html(Testo);
					Testo = parts[0] + '-' + parts[1];
					$('#boxnumero').html(Testo);
                } else {
                    busy = false; // Rilascia lo stato di busy se non ci sono dati
                    setTimeout(chiamanumero, 1000); // Riprova dopo 1 secondo
                }
            },
            error: function(xhr, status, error) {
                busy = false; // Rilascia lo stato di busy in caso di errore
                console.error('Errore nella chiamata:', error);
                alert("errore");
            }
        });
    }
    
    function playSequence(arr, onComplete) {
        let c = 0;
        const audio = document.getElementById('audioPlayer');
        function playNext() {
            if (c < arr.length) {
                audio.src = arr[c];
                audio.play();
                c++;
            } else {
                if (onComplete) onComplete(); // Chiamata dopo la sequenza
            }
        }
        audio.onended = playNext;
        playNext();
    }

function playOne(onComplete) {
    let c = 0;
    const audio = document.getElementById('audioPlayer');
    let audioFiles = [
        'mp3/Turno A.mp3',
        'mp3/379.mp3',
        'mp3/Recarsi allo sportello 1.mp3'
    ];

    function playNext() {
        if (c < audioFiles.length) {
            audio.src = audioFiles[c];
            audio.load();

            // Rimuovi vecchi listener
            audio.oncanplaythrough = null;
            audio.onended = null;

            audio.oncanplaythrough = function() {
                audio.play();
            };

            audio.onended = function() {
                c++; // Incrementa solo dopo la fine
                playNext();
            };
        } else {
            // Nessun altro file da riprodurre
            audio.oncanplaythrough = null;
            audio.onended = null;
            if (typeof onComplete === "function") onComplete();
        }
    }

    playNext();
}

</script>
<script>
    $(document).ready(function() {
        setTimeout(chiamanumero, 2000);
    });

function checkAndReloadAtMidnight() {
  let alreadyReloaded = false;
  setInterval(() => {
    const now = new Date();
    if (now.getHours() === 20 && now.getMinutes() === 00 && !alreadyReloaded) {
      alreadyReloaded = true;
      window.location.reload();
    }
    if (now.getMinutes() !== 0) {
      alreadyReloaded = false; // reset per la mezzanotte successiva
    }
  }, 10000);
}
document.addEventListener("DOMContentLoaded", checkAndReloadAtMidnight);
</script>
</body>

</html>