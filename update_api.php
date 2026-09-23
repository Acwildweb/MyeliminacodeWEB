<?php
/**
 * API JSON aggiornamenti software (solo admin autenticato).
 * Azioni: check (GET/POST), apply (POST).
 */
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$remoteIP = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocalhost = in_array($remoteIP, ['127.0.0.1', '::1'], true)
    || (bool) preg_match('/^10\.213\.134\./', $remoteIP);

function _updateIsLanIP(string $ip): bool
{
    return (bool) preg_match(
        '/^(10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.|::1$|fc[0-9a-f]|fd[0-9a-f])/i',
        $ip
    );
}

if (!$isLocalhost && !_updateIsLanIP($remoteIP)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Accesso non consentito']);
    exit;
}

$authFile = __DIR__ . '/admin_auth.json';
$authData = file_exists($authFile) ? (json_decode((string) file_get_contents($authFile), true) ?: []) : [];
$authKey = 'totem_cfg_' . substr(md5(__DIR__), 0, 8);
$sessionOk = $isLocalhost || (
    isset($_SESSION[$authKey]) &&
    (time() - (int) ($_SESSION[$authKey]['t'] ?? 0)) < 28800 &&
    ($_SESSION[$authKey]['ip'] ?? '') === $remoteIP
);

if (!$sessionOk) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Non autenticato. Accedi ad amministrazione.']);
    exit;
}

require_once __DIR__ . '/update_lib.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($action === 'status') {
        echo json_encode(['ok' => true, 'status' => updateLocalStatus()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'check') {
        try {
            $result = updateCheck();
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            $pub = updatePublicApiError($e);
            http_response_code(!empty($pub['token_invalid']) ? 401 : 500);
            echo json_encode($pub, JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($action === 'save_token') {
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Usa POST per save_token']);
            exit;
        }
        $token = trim((string) ($_POST['token'] ?? ''));
        $result = updateSaveGithubToken($token);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'apply') {
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Usa POST per apply']);
            exit;
        }
        $expected = isset($_POST['sha']) ? trim((string) $_POST['sha']) : '';
        $result = updateApply($expected !== '' ? $expected : null);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Azione non valida. Usa status|check|apply|save_token']);
} catch (Throwable $e) {
    $pub = function_exists('updatePublicApiError') ? updatePublicApiError($e) : ['ok'=>false,'error'=>$e->getMessage()];
    http_response_code(!empty($pub['token_invalid']) ? 401 : 500);
    echo json_encode($pub, JSON_UNESCAPED_UNICODE);
}