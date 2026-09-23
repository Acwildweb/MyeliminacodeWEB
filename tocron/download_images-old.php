<?php
// download_images.php
// Scarica da un servizio remoto l'elenco dei file e salva i file in ./immagini
// Uso: da CLI o browser. Modifica le variabili di configurazione qui sotto.

// ---------------- CONFIGURAZIONE ----------------
$listEndpointBase = 'https://myeliminacode.acwild.eu/appOffline/contactsvr/contactsvr/getfilesgruppo.php'; // endpoint che restituisce l'elenco (il file che hai mostrato)
$remoteFilesBase   = 'https://myeliminacode.acwild.eu/appOffline'; // base per costruire gli URL dei file remoti: {remoteFilesBase}/clienti/{cartella}/[video/]filename
$timeoutSeconds    = 30;
$verifySsl         = true; // mettere false solo per test con cert non validi
// ------------------------------------------------

/**
 * Esegue una GET con cURL e ritorna il body o false in caso di errore.
 */
function curlGet(string $url, int $timeout = 30, bool $verifySsl = true)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_USERAGENT => 'DownloaderScript/1.0',
        CURLOPT_SSL_VERIFYPEER => $verifySsl,
        CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
    ]);
    $body = curl_exec($ch);
    $errNo = curl_errno($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errNo !== 0) {
        error_log("curlGet error: [$errNo] $err for url $url");
        return false;
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("curlGet HTTP $httpCode for url $url");
        return false;
    }
    return $body;
}

function getIdCliente() {
	if (file_exists('/usr/bin/php8.5/home/sslip-bisceglie/htdocs/bisceglie.10-14-201-91.sslip.io/totem_ui_config.json')) {
		$contenuto = file_get_contents('../totem_ui_config.json');

		if ($contenuto === false) {
			return '';
		}

		$dati = json_decode($contenuto, true); // true = array associativo

		if ($dati === null && json_last_error() !== JSON_ERROR_NONE) {
			return '';
		}
		
		$idCliente = $dati['idcliente'] ?? '';
		
		return $idCliente;
	} else {
		return '';
	}
}

/**
 * Scarica un file remoto in modo streaming in $localPath.
 * Restituisce true se ok.
 */
function downloadToFile(string $fileUrl, string $localPath, int $timeout = 60, bool $verifySsl = true): bool
{
    $fp = @fopen($localPath, 'wb');
    if ($fp === false) {
        echo "Impossibile aprire file locale per scrittura: $localPath";
        return false;
    }

    $ch = curl_init($fileUrl);
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_USERAGENT => 'DownloaderScript/1.0',
        CURLOPT_SSL_VERIFYPEER => $verifySsl,
        CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
    ]);

    $ok = curl_exec($ch);
    $errNo = curl_errno($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);
    fclose($fp);

    if ($ok === false || $errNo !== 0 || $httpCode < 200 || $httpCode >= 300) {
        @unlink($localPath);

        $msg = "downloadToFile failed url=$fileUrl http=$httpCode err=[$errNo] $err";

        error_log($msg);   // log
        echo $msg;         // stampa a video

        return false;
    }

    return true;
}

/**
 * Main: chiama l'endpoint lista e poi scarica i file nella cartella 'immagini'.
 *
 * Parametri attesi tramite GET o CLI:
 * - idcliente (obbligatorio)
 * - tipo (opzionale, default '')
 *
 */
function main()
{
    global $listEndpointBase, $remoteFilesBase, $timeoutSeconds, $verifySsl;

    // recupero parametri (supporta CLI e richieste HTTP)
    if (php_sapi_name() === 'cli') {
        $opts = getopt('', ['idcliente:', 'tipo::']);
        $idcliente = $opts['idcliente'] ?? null;
        $tipo = $opts['tipo'] ?? '';
    } else {
        $idcliente = $_GET['idcliente'] ?? null;
        $tipo = $_GET['tipo'] ?? '';
    }

    if (empty($idcliente)) {
		$idcliente = getIdCliente();
		if ($idcliente == '') {
			echo "Parametro idcliente obbligatorio.\n";
			exit(1);
		}
    }

	for ($i = 0; $i <= 1; $i++) {
		if ($i == 0) {
			$tipo = 'i';
		} else {
			$tipo = 'v';
		}
		
		// Costruisco URL per l'elenco file (usa GET idcliente e tipo come il tuo script)
		$listUrl = $listEndpointBase . '?idcliente=' . rawurlencode($idcliente) . '&tipo=' . rawurlencode($tipo);

		echo "Chiamata endpoint elenco: $listUrl\n";
		$resp = curlGet($listUrl, $timeoutSeconds, $verifySsl);
		if ($resp === false) {
			echo "Errore: impossibile ottenere l'elenco dal server remoto. Controlla i log.\n";
			continue;
		}

		$resp = trim($resp);
		if ($resp === 'NOFILE' || $resp === '') {
			echo "Nessun file da scaricare (risposta: NOFILE o vuota).\n";
			continue;
		}

		// La risposta è del tipo "file1.jpg|file2.png§cartella" (come il tuo script)
		$parts = explode('§', $resp, 2);
		$filesPart = $parts[0] ?? '';
		$cartella = $parts[1] ?? '';

		if ($filesPart === '') {
			echo "Formato risposta non valido: elenco file vuoto.\n";
			continue;
		}

		$filenames = array_filter(explode('|', $filesPart), function($v){ return strlen(trim($v))>0; });
		if (count($filenames) === 0) {
			echo "Nessun file valido nell'elenco.\n";
			continue;
		}

		// cartella è necessaria per costruire l'URL remoto (come nel tuo script)
		if ($cartella === '') {
			echo "Attenzione: il servizio non ha restituito la cartella. Il download potrebbe fallire.\n";
		}

		// Directory locale per salvare le immagini
		$localDir = '/usr/bin/php8.5/home/sslip-bisceglie/htdocs/bisceglie.10-14-201-91.sslip.io/immaginicliente';
		if (!is_dir($localDir)) {
			if (!mkdir($localDir, 0755, true)) {
				echo "Errore: impossibile creare la cartella locale $localDir\n";
				exit(1);
			}
		}

		// Per costruire l'URL remoto dei file seguiamo la stessa logica del tuo script:
		// /clienti/{cartella}/[video/]filename
		$subdir = ($tipo === 'v') ? 'video/' : '';
		$baseRemote = rtrim($remoteFilesBase, '/');

		$success = 0;
		$failed = 0;
		foreach ($filenames as $fname) {
			$fname = trim($fname);
			if ($fname === '') continue;

			// Locale: salviamo solo il basename per evitare percorsi malevoli
			$localFile = $localDir . DIRECTORY_SEPARATOR . basename($fname);

			// Costruzione URL remoto: potrebbe essere necessario adattare al tuo server
			// Esempio risultante: https://remote.example.com/clienti/cartella/video/immagine.jpg
			$remoteUrl = $baseRemote . '/gruppi/' . rawurlencode($cartella) . '/' . $subdir . rawurlencode($fname);

			echo "Scaricando: $remoteUrl -> $localFile ... ";

			$ok = downloadToFile($remoteUrl, $localFile, $timeoutSeconds * 2, $verifySsl);
			if ($ok) {
				echo "OK\n";
				$success++;
			} else {
				echo "FAILED\n";
				$failed++;
			}
		}
	}

    echo "\nDownload completati. Successi: $success  Falliti: $failed\n";
}

// Avvio
main();