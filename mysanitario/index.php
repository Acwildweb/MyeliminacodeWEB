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
            <?php if ($img): ?>
            background-image: url('<?php echo htmlspecialchars($img_url); ?>');
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: center center;
            background-attachment: fixed;
            <?php endif; ?>
        }
    </style>
</head>
<body>
<button onclick="playOne();" id="buttonplay">Play</button>
<audio id="audioPlayer"></audio>
<?php if (!$img): ?>
        <p>Nessuna immagine di sfondo trovata.</p>
<?php endif; ?>
<?php if ($box_news == '1') { ?>
    <div id="boxnews"
     data-left="<?php echo $box_news_left_db; ?>"
     data-top="<?php echo $box_news_top_db; ?>"
     data-width="<?php echo $box_news_width_db; ?>"
     data-height="<?php echo $box_news_height_db; ?>"
     data-base-width="<?php echo $ximmaginesfondo; ?>"
     data-base-height="<?php echo $yimmaginesfondo; ?>"
     style="position:absolute;">
        <iframe src="<?php echo $box_news_url; ?>"
          style="width: 100%; height: 100%; border: none; overflow: hidden;"
          scrolling="no"
          allowfullscreen
          loading="lazy">
        </iframe>
    </div>
<?php } ?>
<?php if ($box_immagini == '1') { ?>
    <div id="boximmagini"
     data-left="<?php echo $box_immagini_left_db; ?>"
     data-top="<?php echo $box_immagini_top_db; ?>"
     data-width="<?php echo $box_immagini_width_db; ?>"
     data-height="<?php echo $box_immagini_height_db; ?>"
     data-base-width="<?php echo $ximmaginesfondo; ?>"
     data-base-height="<?php echo $yimmaginesfondo; ?>"
     style="position:absolute;">
        <img src="img/placeholder.png" alt="Placeholder" id="imgboximmagini" style="width: 100%; height: 100%;">
        <!--video id="myVideo" controls style="width: 100%; height: 100%; display:none;"
               poster="img/placeholder.png" preload="auto">
            <source id="videoboxvideo" src="" type="video/mp4">
            Your browser does not support the video tag.
        </video-->
    </div>
<?php } 
    $font_size_chiamati = 30; // Dimensione del font per il box chiamati
?>
<?php foreach ($turni as $turno): 
    //$font_size_chiamati = $turno['sizeturno']; // Dimensione del font di default
?>
    <div id="boxturno_<?php echo $turno['idturno']; ?>"
     data-left="<?php echo $turno['xturno']; ?>"
     data-top="<?php echo $turno['yturno']; ?>"
     data-width="<?php echo $turno['wturno']; ?>"
     data-height="<?php echo $turno['hturno']; ?>"
     data-base-width="<?php echo $ximmaginesfondo; ?>"
     data-base-height="<?php echo $yimmaginesfondo; ?>"
     style="position:absolute; 
        font-family: <?php echo htmlspecialchars($turno['fontturno']) ?>, sans-serif;
        font-size: <?php echo htmlspecialchars($turno['sizeturno']) ?>px;
        color: <?php echo htmlspecialchars($turno['coloreturno']) ?>;
        <?php if ($turno['boldturno']): ?>font-weight: bold;<?php endif; ?>">
            <?php echo htmlspecialchars($turno['nometurno']); ?>
    </div>
<?php endforeach; ?>
<?php if ($box_chiamati == '1') { ?>
    <div id="boxchiamati"
     data-left="<?php echo $box_chiamati_left_db; ?>"
     data-top="<?php echo $box_chiamati_top_db; ?>"
     data-width="<?php echo $box_chiamati_width_db; ?>"
     data-height="<?php echo $box_chiamati_height_db; ?>"
     data-base-width="<?php echo $ximmaginesfondo; ?>"
     data-base-height="<?php echo $yimmaginesfondo; ?>"
     style="position:absolute; 
        display: flex;
        justify-content: center;
        align-items: flex-start;
        font-family: <?php echo htmlspecialchars($box_chiamati_font) ?>, sans-serif;
        font-size: <?php echo htmlspecialchars($font_size_chiamati) ?>px;
        <?php if ($box_chiamati_bold): ?>font-weight: bold;<?php endif; ?>">
        <span id="boxchiamati_text">Ultimi numeri chiamati</span>
    </div>
<?php } ?>
<?php foreach ($contatori as $contatore): ?>
    <div id="boxcontatore_<?php echo $contatore['idturno']; ?>"
     data-left="<?php echo $contatore['xcontatore']; ?>"
     data-top="<?php echo $contatore['ycontatore']; ?>"
     data-width="<?php echo $contatore['wcontatore']; ?>"
     data-height="<?php echo $contatore['hcontatore']; ?>"
     data-base-width="<?php echo $ximmaginesfondo; ?>"
     data-base-height="<?php echo $yimmaginesfondo; ?>"
     style="position:absolute; display:flex; justify-content:center; align-items:center; 
        font-family: <?php echo htmlspecialchars($contatore['fontcontatore']) ?>, sans-serif;
        font-size: <?php echo htmlspecialchars($contatore['sizecontatore']) ?>px;
        color: <?php echo htmlspecialchars($contatore['colorecontatore']) ?>;
        <?php if ($contatore['boldcontatore']): ?>font-weight: bold;<?php endif; ?>">
            0
    </div>
<?php endforeach; ?>
<?php foreach ($postazioni as $postazione): ?>
    <div id="boxpostazione_<?php echo $postazione['idturno']; ?>"
     data-left="<?php echo $postazione['xpostazione']; ?>"
     data-top="<?php echo $postazione['ypostazione']; ?>"
     data-width="<?php echo $postazione['wpostazione']; ?>"
     data-height="<?php echo $postazione['hpostazione']; ?>"
     data-base-width="<?php echo $ximmaginesfondo; ?>"
     data-base-height="<?php echo $yimmaginesfondo; ?>"
     style="position:absolute; display:flex; justify-content:center; align-items:center; 
        font-family: <?php echo htmlspecialchars($postazione['fontpostazione']) ?>, sans-serif;
        font-size: <?php echo htmlspecialchars($postazione['sizepostazione']) ?>px;
        color: <?php echo htmlspecialchars($postazione['colorepostazione']) ?>;
        <?php if ($postazione['boldpostazione']): ?>font-weight: bold;<?php endif; ?>">
            0
    </div>
<?php endforeach; ?>
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
    box.style.left = (boxLeft * scalaX) + "px";
    box.style.top = (boxTop * scalaY) + "px";
    box.style.width = (boxWidth * scalaX) + "px";
    box.style.height = (boxHeight * scalaY) + "px";
}

// Chiama la funzione al caricamento e al resize
window.addEventListener('load', function() {
<?php if ($box_news == '1') { ?>
    posizionaBox('boxnews');
<?php } ?>
<?php if ($box_immagini == '1') { ?>
    posizionaBox('boximmagini');
<?php } ?>
<?php if ($box_chiamati == '1') { ?>
    posizionaBox('boxchiamati');
<?php } ?>
<?php foreach ($turni as $turno): ?>
    posizionaBox('boxturno_<?php echo $turno['idturno']; ?>');
    posizionaBox('boxcontatore_<?php echo $turno['idturno']; ?>');
    posizionaBox('boxpostazione_<?php echo $turno['idturno']; ?>');
<?php endforeach; ?>
});
window.addEventListener('resize', function() {
<?php if ($box_news == '1') { ?>
    posizionaBox('boxnews');
<?php } ?>
<?php foreach ($turni as $turno): ?>
    posizionaBox('boxturno_<?php echo $turno['idturno']; ?>');
    posizionaBox('boxcontatore_<?php echo $turno['idturno']; ?>');
    posizionaBox('boxpostazione_<?php echo $turno['idturno']; ?>');
<?php endforeach; ?>
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
	console.log('Entro');
	if (busy) console.log('Occupato');
        if (busy) return;
        busy = true; // Imposta busy a true per evitare chiamate multiple
        console.log('Chiamo chiamanumero');
        $.ajax({
            url: 'chiamanumero.php',
            type: 'POST',
            data: { action: 'chiamanumero', ipmonitor: '<?php echo $ipclient; ?>'},
            success: function(response) {
		console.log('Chiamata fatta');
                if (response != '') {
		    console.log('Response:'+response);
                    let parts = response.split('|');
                    let audioFiles = [
                        `mp3/Turno ${parts[0]}.mp3`,
                        `mp3/${parts[1]}.mp3`,
                        `mp3/Recarsi allo sportello ${parts[2]}.mp3`
                    ];
                    let sportello = parts[2];
                    let idturno = parts[4];
                    let iddivpostazione = '#boxpostazione_' + idturno;
                    let iddivcontatore = '#boxcontatore_' + idturno;
                    $(iddivpostazione).text(sportello);
                    $(iddivcontatore).text(parts[1]);
		    console.log('playsequence prima');
                    playSequence(audioFiles, function() {
			console.log('playsequence');
                        busy = false; // Rilascia lo stato di busy dopo la sequenza
                        setTimeout(() => {
                            chiamanumero();
                        }, 1000);
                    });
                    for (i = 4; i > 0; i--) {
    			vChiamati[i] = vChiamati[i - 1];
		    }
                    vChiamati[0] = parts[0] + '-' + parts[1] + ' sportello ' + parts[2];
                    Testo = 'Ultimi numeri chiamati';
		    for (i=0; i<5; i++) {
			Testo = Testo + '<br>' + vChiamati[i];
		    }
		    $('#boxchiamati_text').html(Testo);
                } else {
                    busy = false; // Rilascia lo stato di busy se non ci sono dati
                    setTimeout(chiamanumero, 1000); // Riprova dopo 1 secondo
                }
            },
            error: function(xhr, status, error) {
                busy = false; // Rilascia lo stato di busy in caso di errore
                console.error('Errore nella chiamata:', error);
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
<?php
    if ($box_immagini == '1') {
?>
    var images;
    var videos;
    var imgattuale = 0;
    var numimmagini = 0;
    var vidattuale = 0;
    var numvideo = 0;
    var fase = "immagini"; // "immagini" o "video"
    var timer = null;

    function getImmagini() {
        $.ajax({
            url: 'getimmagini.php',
            type: 'POST',
            dataType: 'text',
            success: function(response) {
                console.log('Immagini caricate:', response);
                images = response.split('|');
                numimmagini = images.length;
            },
            error: function(xhr, status, error) {
                console.error('Errore nel caricamento delle immagini:', error);
            }
        });
    }

    function getVideo() {
        $.ajax({
            url: 'getvideo.php',
            type: 'POST',
            dataType: 'text',
            success: function(response) {
                console.log('Video caricati:', response);
                videos = response.split('|');
                numvideo = videos.length;
            },
            error: function(xhr, status, error) {
                console.error('Errore nel caricamento dei video:', error);
            }
        });
    }

    // Visualizza una immagine e gestisce la rotazione immagini-video
    function mostraImmagine() {
        if (Array.isArray(images) && images.length > 0) {
            $("#imgboximmagini").show();
            //$("#myVideo").hide();

            let img = document.getElementById("imgboximmagini");
            img.src = 'immaginicliente/' + images[imgattuale];
            imgattuale++;

            if (imgattuale >= numimmagini) {
                // Finite le immagini, passo al video
                imgattuale = 0;
                fase = "video";
                clearTimeout(timer);
                setTimeout(mostraVideo, 5000); // attendo 5 secondi prima di passare al video
            } else {
                // Passo alla prossima immagine tra 5 secondi
                clearTimeout(timer);
                timer = setTimeout(mostraImmagine, 5000);
            }
        } else {
            setTimeout(mostraImmagine, 5000);
        }
    }

    // Visualizza un video e, alla fine, riparte dalle immagini
    function mostraVideo() {
        if (Array.isArray(videos) && videos.length > 0) {
            $("#imgboximmagini").hide();
            //$("#myVideo").show();

            /*let vid = document.getElementById("myVideo");
            vid.src = 'videocliente/' + videos[vidattuale];
            vid.currentTime = 0;
            vid.muted = true; // Imposta il video come muto per consentire l'autoplay
            vid.load();*/
            
            // Utilizzo una Promise per gestire la riproduzione
            /*let playPromise = vid.play();
            
            if (playPromise !== undefined) {
                playPromise.then(_ => {
                    // La riproduzione è iniziata con successo
                    console.log("Video avviato con successo");
                    // Dopo un breve ritardo, riattiva l'audio se necessario
                    setTimeout(() => {
                        vid.muted = false;
                    }, 500);
                })
                .catch(error => {
                    console.error("Errore nella riproduzione automatica:", error);
                    // Fallback: mostra un pulsante di play o passa alla prossima immagine
                    vidattuale = (vidattuale + 1) % numvideo;
                    fase = "immagini";
                    mostraImmagine();
                });
            }*/

            // Quando il video termina
            /*vid.onended = function() {
                vidattuale = (vidattuale + 1) % numvideo;
                fase = "immagini";
                mostraImmagine();
            };*/
        } else {
            // Nessun video, riparti dalle immagini
            fase = "immagini";
            mostraImmagine();
        }
    }

    $(document).ready(function() {
        getImmagini();
        getVideo();
        setInterval(getImmagini, 360000); // Aggiorna le immagini ogni ora
        setInterval(getVideo, 360000);    // Aggiorna i video ogni ora

        // Avvia dal ciclo immagini
        mostraImmagine();
    });
<?php
    }
?>

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

// Auto-refresh quando la configurazione cambia dall'admin
let monitorConfigVersion = null;
async function checkMonitorConfigVersion() {
    try {
        const r = await fetch('/totem/api/config_version.php?_=' + Date.now());
        const d = await r.json();
        if (monitorConfigVersion === null) {
            monitorConfigVersion = d.version;
        } else if (d.version !== monitorConfigVersion) {
            console.log('Configurazione aggiornata, ricarico monitor...');
            window.location.reload();
        }
    } catch(e) { console.log('Check versione fallito'); }
}
// Controlla ogni 3 secondi
checkMonitorConfigVersion();
setInterval(checkMonitorConfigVersion, 3000);
</script>
</body>

</html>