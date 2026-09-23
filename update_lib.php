<?php
/**
 * Libreria aggiornamenti software via GitHub API (zipball).
 * Non richiede git sul server.
 */

function updateAppRoot(): string
{
    return __DIR__;
}

function updateConfigPath(): string
{
    return updateAppRoot() . DIRECTORY_SEPARATOR . 'update_config.json';
}

function updateVersionPath(): string
{
    return updateAppRoot() . DIRECTORY_SEPARATOR . 'VERSION.json';
}

function updateIgnorePath(): string
{
    return updateAppRoot() . DIRECTORY_SEPARATOR . '.updateignore';
}

function updateLockPath(): string
{
    return updateAppRoot() . DIRECTORY_SEPARATOR . 'update_tmp' . DIRECTORY_SEPARATOR . 'apply.lock';
}

function updateLog(string $msg): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    @file_put_contents(updateAppRoot() . DIRECTORY_SEPARATOR . 'update.log', $line, FILE_APPEND | LOCK_EX);
}

function updateDetectBranch(?array $cfg = null): string
{
    if (is_array($cfg) && !empty($cfg['branch'])) {
        return (string) $cfg['branch'];
    }
    return (PHP_OS_FAMILY === 'Windows') ? 'windows-iis' : 'linux';
}

function updateLoadConfig(): array
{
    $path = updateConfigPath();
    if (!is_file($path)) {
        throw new RuntimeException('Manca update_config.json. Copia update_config.example.json e inserisci owner/repo/token. Vedi UPDATE_SETUP.md');
    }
    $cfg = json_decode((string) file_get_contents($path), true);
    if (!is_array($cfg)) {
        throw new RuntimeException('update_config.json non valido (JSON).');
    }
    foreach (['owner', 'repo', 'token'] as $k) {
        if (empty($cfg[$k]) || !is_string($cfg[$k]) || str_contains((string) $cfg[$k], 'TUO_') || str_contains((string) $cfg[$k], 'xxxxxxxx')) {
            throw new RuntimeException("Config incompleta: valorizza \"$k\" in update_config.json");
        }
    }
    if (!isset($cfg['protected_paths']) || !is_array($cfg['protected_paths'])) {
        $cfg['protected_paths'] = [];
    }
    $cfg['branch'] = updateDetectBranch($cfg);
    return $cfg;
}

function updateLoadVersion(): array
{
    $path = updateVersionPath();
    if (!is_file($path)) {
        return ['version' => '0.0.0', 'commit' => '', 'branch' => updateDetectBranch(), 'built_at' => ''];
    }
    $v = json_decode((string) file_get_contents($path), true);
    return is_array($v) ? $v : ['version' => '0.0.0', 'commit' => '', 'branch' => updateDetectBranch(), 'built_at' => ''];
}

function updateSaveVersion(array $v): void
{
    $json = json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    if (@file_put_contents(updateVersionPath(), $json, LOCK_EX) === false) {
        throw new RuntimeException('Impossibile scrivere VERSION.json');
    }
}

function updateLoadIgnorePatterns(array $cfg): array
{
    $patterns = [];
    foreach ($cfg['protected_paths'] as $p) {
        $patterns[] = str_replace('\\', '/', trim((string) $p));
    }
    $ignoreFile = updateIgnorePath();
    if (is_file($ignoreFile)) {
        foreach (file($ignoreFile, FILE_IGNORE_NEW_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $patterns[] = str_replace('\\', '/', $line);
        }
    }
    // Sempre protetti
    $patterns = array_merge($patterns, [
        'connect.php',
        'update_config.json',
        'admin_auth.json',
        'backups',
        'update_tmp',
        '.git',
    ]);
    return array_values(array_unique(array_filter($patterns)));
}

function updatePathIsProtected(string $rel, array $patterns): bool
{
    $rel = ltrim(str_replace('\\', '/', $rel), '/');
    if ($rel === '' || $rel === '.' ) {
        return true;
    }
    foreach ($patterns as $pat) {
        $pat = ltrim(str_replace('\\', '/', $pat), '/');
        if ($pat === '') {
            continue;
        }
        // directory prefix
        if (!str_contains($pat, '*') && !str_contains($pat, '?')) {
            if ($rel === $pat || str_starts_with($rel, rtrim($pat, '/') . '/')) {
                return true;
            }
            continue;
        }
        // glob
        if (fnmatch($pat, $rel) || fnmatch($pat, basename($rel))) {
            return true;
        }
    }
    return false;
}

function updateHttpJson(string $url, string $token, int $timeout = 30): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => [
            'Accept: application/vnd.github+json',
            'Authorization: Bearer ' . $token,
            'X-GitHub-Api-Version: 2022-11-28',
            'User-Agent: MySanitario-UpdateClient/1.0',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($body === false) {
        throw new RuntimeException('GitHub API curl error: ' . $err);
    }
    $data = json_decode($body, true);
    if ($code < 200 || $code >= 300) {
        $msg = is_array($data) && isset($data['message']) ? $data['message'] : ('HTTP ' . $code);
        throw new RuntimeException('GitHub API: ' . $msg);
    }
    if (!is_array($data)) {
        throw new RuntimeException('Risposta GitHub non JSON');
    }
    return $data;
}

function updateHttpDownload(string $url, string $token, string $destFile, int $timeout = 300): void
{
    $fp = fopen($destFile, 'wb');
    if (!$fp) {
        throw new RuntimeException('Impossibile creare file temp download');
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => [
            'Accept: application/vnd.github+json',
            'Authorization: Bearer ' . $token,
            'X-GitHub-Api-Version: 2022-11-28',
            'User-Agent: MySanitario-UpdateClient/1.0',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $ok = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    fclose($fp);
    if ($ok === false || $code < 200 || $code >= 300) {
        @unlink($destFile);
        throw new RuntimeException('Download zipball fallito: HTTP ' . $code . ' ' . $err);
    }
}

function updateCheck(): array
{
    $cfg = updateLoadConfig();
    $local = updateLoadVersion();
    $branch = $cfg['branch'];
    $url = sprintf(
        'https://api.github.com/repos/%s/%s/commits/%s',
        rawurlencode($cfg['owner']),
        rawurlencode($cfg['repo']),
        rawurlencode($branch)
    );
    $remote = updateHttpJson($url, $cfg['token']);
    $remoteSha = (string) ($remote['sha'] ?? '');
    if ($remoteSha === '') {
        throw new RuntimeException('Commit remoto senza SHA');
    }
    $localSha = (string) ($local['commit'] ?? '');
    $available = ($localSha === '' || !hash_equals($localSha, $remoteSha));
    $msg = $remote['commit']['message'] ?? '';
    $msg = is_string($msg) ? trim(explode("\n", $msg)[0]) : '';

    // Prova a leggere VERSION.json remoto via contents API (best-effort)
    $remoteVersion = null;
    try {
        $cUrl = sprintf(
            'https://api.github.com/repos/%s/%s/contents/VERSION.json?ref=%s',
            rawurlencode($cfg['owner']),
            rawurlencode($cfg['repo']),
            rawurlencode($branch)
        );
        $content = updateHttpJson($cUrl, $cfg['token']);
        if (!empty($content['content'])) {
            $decoded = base64_decode(str_replace("\n", '', $content['content']), true);
            $jv = json_decode((string) $decoded, true);
            if (is_array($jv) && !empty($jv['version'])) {
                $remoteVersion = (string) $jv['version'];
            }
        }
    } catch (Throwable $e) {
        // ignore
    }

    return [
        'ok' => true,
        'status' => $available ? 'update_available' : 'up_to_date',
        'update_available' => $available,
        'branch' => $branch,
        'os' => PHP_OS_FAMILY,
        'local' => [
            'version' => $local['version'] ?? '0.0.0',
            'commit' => $localSha,
            'built_at' => $local['built_at'] ?? '',
        ],
        'remote' => [
            'version' => $remoteVersion,
            'commit' => $remoteSha,
            'message' => $msg,
            'date' => $remote['commit']['author']['date'] ?? '',
            'html_url' => $remote['html_url'] ?? '',
        ],
        'repo' => $cfg['owner'] . '/' . $cfg['repo'],
        'checked_at' => date('c'),
    ];
}

function updateRmTree(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $p = $f->getPathname();
        if ($f->isDir()) {
            @rmdir($p);
        } else {
            @unlink($p);
        }
    }
    @rmdir($dir);
}

function updateListFilesRecursive(string $root): array
{
    $out = [];
    $root = rtrim(str_replace('\\', '/', $root), '/');
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $f) {
        if (!$f->isFile()) {
            continue;
        }
        $full = str_replace('\\', '/', $f->getPathname());
        $rel = ltrim(substr($full, strlen($root)), '/');
        $out[] = $rel;
    }
    sort($out);
    return $out;
}

function updateAcquireLock(): void
{
    $dir = updateAppRoot() . DIRECTORY_SEPARATOR . 'update_tmp';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        throw new RuntimeException('Impossibile creare update_tmp');
    }
    $lock = updateLockPath();
    if (is_file($lock)) {
        $age = time() - (int) @filemtime($lock);
        if ($age < 1800) {
            throw new RuntimeException('Aggiornamento già in corso (lock attivo). Riprova tra poco.');
        }
        @unlink($lock);
    }
    if (@file_put_contents($lock, (string) getmypid(), LOCK_EX) === false) {
        throw new RuntimeException('Impossibile acquisire lock aggiornamento');
    }
}

function updateReleaseLock(): void
{
    @unlink(updateLockPath());
}

/**
 * Applica aggiornamento allo sha remoto (tipicamente da check precedente).
 * @return array esito
 */
function updateApply(?string $expectedSha = null): array
{
    $cfg = updateLoadConfig();
    $patterns = updateLoadIgnorePatterns($cfg);
    $check = updateCheck();
    if (!$check['update_available']) {
        return [
            'ok' => true,
            'applied' => false,
            'message' => 'Nessun aggiornamento disponibile',
            'check' => $check,
        ];
    }
    $sha = (string) $check['remote']['commit'];
    if ($expectedSha !== null && $expectedSha !== '' && !hash_equals($expectedSha, $sha)) {
        throw new RuntimeException('Lo SHA remoto è cambiato dall’ultima verifica. Riesegui Verifica.');
    }

    updateAcquireLock();
    $tmpRoot = updateAppRoot() . DIRECTORY_SEPARATOR . 'update_tmp';
    $zipFile = $tmpRoot . DIRECTORY_SEPARATOR . 'update.zip';
    $extractDir = $tmpRoot . DIRECTORY_SEPARATOR . 'extract';
    $backupDir = updateAppRoot() . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'update_' . date('Ymd_His');

    $copied = [];
    $backed = [];
    try {
        updateLog('APPLY start sha=' . $sha . ' branch=' . $cfg['branch']);
        updateRmTree($extractDir);
        if (!@mkdir($extractDir, 0755, true)) {
            throw new RuntimeException('Impossibile creare cartella extract');
        }
        @unlink($zipFile);

        $zipUrl = sprintf(
            'https://api.github.com/repos/%s/%s/zipball/%s',
            rawurlencode($cfg['owner']),
            rawurlencode($cfg['repo']),
            rawurlencode($sha)
        );
        updateHttpDownload($zipUrl, $cfg['token'], $zipFile);

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new RuntimeException('ZIP non valido');
        }
        if (!$zip->extractTo($extractDir)) {
            $zip->close();
            throw new RuntimeException('Estrazione ZIP fallita');
        }
        $zip->close();

        // GitHub zipball ha una cartella root owner-repo-sha/
        $entries = array_values(array_filter(scandir($extractDir) ?: [], fn($e) => $e !== '.' && $e !== '..'));
        if (count($entries) !== 1 || !is_dir($extractDir . DIRECTORY_SEPARATOR . $entries[0])) {
            throw new RuntimeException('Struttura zipball inattesa');
        }
        $payloadRoot = $extractDir . DIRECTORY_SEPARATOR . $entries[0];
        $remoteFiles = updateListFilesRecursive($payloadRoot);

        if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true)) {
            throw new RuntimeException('Impossibile creare cartella backup');
        }

        foreach ($remoteFiles as $rel) {
            if (updatePathIsProtected($rel, $patterns)) {
                continue;
            }
            // Non aggiornare VERSION.json dallo zip: lo riscriviamo noi a fine apply
            if ($rel === 'VERSION.json') {
                continue;
            }
            $src = $payloadRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            $dst = updateAppRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if (!is_file($src)) {
                continue;
            }

            if (is_file($dst)) {
                $bak = $backupDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
                $bakDir = dirname($bak);
                if (!is_dir($bakDir) && !@mkdir($bakDir, 0755, true)) {
                    throw new RuntimeException('Backup mkdir fallita: ' . $rel);
                }
                if (!@copy($dst, $bak)) {
                    throw new RuntimeException('Backup fallito: ' . $rel);
                }
                $backed[] = $rel;
            }

            $dstDir = dirname($dst);
            if (!is_dir($dstDir) && !@mkdir($dstDir, 0755, true)) {
                throw new RuntimeException('Mkdir destinazione fallita: ' . $rel);
            }
            if (!@copy($src, $dst)) {
                throw new RuntimeException('Copia fallita: ' . $rel);
            }
            $copied[] = $rel;
        }

        $newVer = [
            'version' => $check['remote']['version'] ?: ($check['local']['version'] ?? '1.0.0'),
            'commit' => $sha,
            'branch' => $cfg['branch'],
            'built_at' => date('c'),
        ];
        // se VERSION remoto aveva version, già in check; altrimenti mantieni locale e bump patch soft
        if (empty($check['remote']['version'])) {
            $newVer['version'] = (string) ($check['local']['version'] ?? '1.0.0');
        }
        updateSaveVersion($newVer);

        updateLog('APPLY ok files=' . count($copied) . ' backed=' . count($backed));

        return [
            'ok' => true,
            'applied' => true,
            'message' => 'Aggiornamento completato',
            'sha' => $sha,
            'branch' => $cfg['branch'],
            'backup_dir' => 'backups/' . basename($backupDir),
            'files_updated' => count($copied),
            'files_backed_up' => count($backed),
            'files' => array_slice($copied, 0, 200),
            'version' => $newVer,
        ];
    } catch (Throwable $e) {
        updateLog('APPLY ERROR: ' . $e->getMessage());
        throw $e;
    } finally {
        @unlink($zipFile);
        updateRmTree($extractDir);
        updateReleaseLock();
    }
}

function updateLocalStatus(): array
{
    $local = updateLoadVersion();
    $cfgOk = is_file(updateConfigPath());
    $branch = $local['branch'] ?? updateDetectBranch();
    try {
        if ($cfgOk) {
            $cfg = updateLoadConfig();
            $branch = $cfg['branch'];
        }
    } catch (Throwable $e) {
        // config incompleta: status parziale
    }
    return [
        'version' => $local['version'] ?? '0.0.0',
        'commit' => $local['commit'] ?? '',
        'branch' => $branch,
        'built_at' => $local['built_at'] ?? '',
        'os' => PHP_OS_FAMILY,
        'config_present' => $cfgOk,
    ];
}