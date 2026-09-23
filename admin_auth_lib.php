<?php
/**
 * Autenticazione amministrazione — utenti e permessi in system_users.json (no DB).
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const ADMIN_PAGES = [
    'amministrazione'   => ['label' => 'Grafica Totem',       'file' => 'amministrazione.php',   'icon' => '&#127912;', 'group' => 'Totem'],
    'config_stampante'  => ['label' => 'Stampante',           'file' => 'config_stampante.php',  'icon' => '&#128424;', 'group' => 'Totem'],
    'config_sportelli'  => ['label' => 'Sportelli / Click',   'file' => 'config_sportelli.php',  'icon' => '&#128251;', 'group' => 'Totem'],
    'config_monitor2'   => ['label' => 'Monitor Coda',        'file' => 'config_monitor2.php',   'icon' => '&#128250;', 'group' => 'Monitor'],
    'gestione_utenti'   => ['label' => 'Gestione utenti',     'file' => 'gestione_utenti.php',   'icon' => '&#128101;', 'group' => 'Sistema'],
    'stampa_errori'     => ['label' => 'Log Stampa',          'file' => 'stampa_errori_view.php','icon' => '&#128203;', 'group' => 'Sistema', 'target' => '_blank'],
];

function admin_users_file(): string
{
    return __DIR__ . '/system_users.json';
}

function admin_is_localhost(string $ip): bool
{
    return in_array($ip, ['127.0.0.1', '::1'], true);
}

function admin_is_lan_ip(string $ip): bool
{
    return (bool)preg_match(
        '/^(10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.|::1$|fc[0-9a-f]|fd[0-9a-f])/i',
        $ip
    );
}

function admin_session_key(): string
{
    return 'totem_cfg_' . substr(md5(__DIR__), 0, 8);
}

function admin_all_page_keys(): array
{
    return array_keys(ADMIN_PAGES);
}

function admin_default_pages(bool $all = true): array
{
    $pages = [];
    foreach (admin_all_page_keys() as $key) {
        $pages[$key] = $all;
    }
    return $pages;
}

function admin_migrate_legacy_users(): array
{
    $hash = null;
    $legacyFile = __DIR__ . '/admin_auth.json';
    if (file_exists($legacyFile)) {
        $legacy = json_decode(file_get_contents($legacyFile), true) ?: [];
        $hash = $legacy['password_hash'] ?? null;
    }
    if (!$hash) {
        $hash = password_hash('admin', PASSWORD_DEFAULT);
    }

    return [
        'version' => 1,
        'users' => [[
            'id' => 'u_admin',
            'username' => 'admin',
            'password_hash' => $hash,
            'name' => 'Amministratore',
            'active' => true,
            'is_superadmin' => true,
            'pages' => admin_default_pages(true),
        ]],
    ];
}

function admin_load_users_store(): array
{
    $file = admin_users_file();
    if (!file_exists($file)) {
        $store = admin_migrate_legacy_users();
        admin_save_users_store($store);
        return $store;
    }

    $store = json_decode(file_get_contents($file), true);
    if (!is_array($store) || empty($store['users']) || !is_array($store['users'])) {
        $store = admin_migrate_legacy_users();
        admin_save_users_store($store);
    }

    return $store;
}

function admin_save_users_store(array $store): bool
{
    if (empty($store['users'])) {
        return false;
    }
    $store['version'] = 1;
    $json = json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return (bool)file_put_contents(admin_users_file(), $json, LOCK_EX);
}

function admin_find_user_by_username(string $username): ?array
{
    $username = strtolower(trim($username));
    foreach (admin_load_users_store()['users'] as $user) {
        if (strtolower($user['username'] ?? '') === $username) {
            return $user;
        }
    }
    return null;
}

function admin_find_user_by_id(string $id): ?array
{
    foreach (admin_load_users_store()['users'] as $user) {
        if (($user['id'] ?? '') === $id) {
            return $user;
        }
    }
    return null;
}

function admin_user_can_access(array $user, string $pageKey): bool
{
    if (!($user['active'] ?? false)) {
        return false;
    }
    if (!empty($user['is_superadmin'])) {
        return true;
    }
    return !empty($user['pages'][$pageKey]);
}

/** Prima pagina admin consentita all'utente (per redirect post-login). */
function admin_first_allowed_page(?array $user): ?string
{
    if (!$user) {
        return null;
    }
    foreach (ADMIN_PAGES as $key => $page) {
        if ($key === 'stampa_errori') {
            continue;
        }
        if (admin_user_can_access($user, $key)) {
            return $page['file'];
        }
    }
    if (admin_user_can_access($user, 'stampa_errori')) {
        return ADMIN_PAGES['stampa_errori']['file'];
    }
    return null;
}

function admin_current_script(): string
{
    return basename($_SERVER['SCRIPT_NAME'] ?? '');
}

function admin_session_user(): ?array
{
    $key = admin_session_key();
    if (empty($_SESSION[$key]['user_id'])) {
        return null;
    }
    return admin_find_user_by_id((string)$_SESSION[$key]['user_id']);
}

function admin_session_valid(string $remoteIP): bool
{
    $key = admin_session_key();
    if (empty($_SESSION[$key])) {
        return false;
    }
    $session = $_SESSION[$key];
    if ((time() - (int)($session['t'] ?? 0)) >= 28800) {
        return false;
    }
    if (($session['ip'] ?? '') !== $remoteIP) {
        return false;
    }
    $user = admin_find_user_by_id((string)($session['user_id'] ?? ''));
    return $user !== null && ($user['active'] ?? false);
}

function admin_set_session_user(array $user, string $remoteIP): void
{
    $_SESSION[admin_session_key()] = [
        't' => time(),
        'ip' => $remoteIP,
        'user_id' => $user['id'],
        'username' => $user['username'],
    ];
}

function admin_clear_session(): void
{
    unset($_SESSION[admin_session_key()]);
}

function admin_using_default_password(): bool
{
    $user = admin_find_user_by_username('admin');
    if (!$user) {
        return true;
    }
    return password_verify('admin', $user['password_hash'] ?? '');
}

function admin_render_login(string $title, string $subtitle, string $error = '', bool $showDefaultWarn = false): void
{
    http_response_code($error !== '' ? 401 : 200);
    ?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars($title); ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Segoe UI,Arial,sans-serif;background:linear-gradient(160deg,#0d1b3e 0%,#1a3a6e 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}
.login-box{background:rgba(255,255,255,.07);backdrop-filter:blur(14px);border:1px solid rgba(100,160,255,.25);border-radius:16px;padding:44px 36px;width:100%;max-width:400px;color:#fff;text-align:center}
.login-box .lock{font-size:52px;margin-bottom:14px}
.login-box h1{font-size:20px;margin-bottom:6px}
.login-box p{font-size:13px;opacity:.6;margin-bottom:26px}
input[type=text],input[type=password]{width:100%;padding:12px 16px;border:1px solid rgba(255,255,255,.25);border-radius:8px;background:rgba(255,255,255,.1);color:#fff;font-size:15px;outline:none;margin-bottom:14px}
input:focus{border-color:#42a5f5}
.btn-login{width:100%;padding:12px;background:linear-gradient(135deg,#1565c0,#0d47a1);border:none;border-radius:8px;color:#fff;font-size:15px;font-weight:600;cursor:pointer}
.err{background:rgba(220,53,69,.2);border:1px solid rgba(220,53,69,.5);border-radius:6px;padding:10px;font-size:13px;margin-bottom:14px;color:#f99}
.warn{background:rgba(255,193,7,.15);border:1px solid rgba(255,193,7,.4);border-radius:6px;padding:10px 12px;font-size:12px;margin-bottom:16px;color:#ffd54f;text-align:left;line-height:1.6}
</style>
</head>
<body>
<div class="login-box">
    <div class="lock">&#128274;</div>
    <h1><?php echo htmlspecialchars($title); ?></h1>
    <p><?php echo htmlspecialchars($subtitle); ?></p>
    <?php if ($showDefaultWarn): ?>
    <div class="warn">Stai usando le credenziali predefinite <strong>admin / admin</strong>.<br>
        Cambiale in <em>Gestione utenti</em> dopo l'accesso.</div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div class="err"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
        <input type="text" name="_lan_user" placeholder="Utente" value="admin" autocomplete="username">
        <input type="password" name="_lan_pwd" placeholder="Password" autofocus autocomplete="current-password">
        <button type="submit" class="btn-login">Accedi</button>
    </form>
</div>
</body>
</html>
    <?php
    exit;
}

function admin_require_page(string $pageKey, string $loginTitle = 'Accesso amministrazione', string $loginSubtitle = 'Accesso da rete LAN — inserire utente e password'): void
{
    $remoteIP = $_SERVER['REMOTE_ADDR'] ?? '';
    $isLocalhost = admin_is_localhost($remoteIP);

    if (!$isLocalhost && !admin_is_lan_ip($remoteIP)) {
        http_response_code(403);
        die('<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><title>Accesso negato</title>'
           . '<style>body{font-family:Segoe UI,sans-serif;background:#0d1b3e;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}'
           . '.b{text-align:center;padding:40px;background:rgba(255,255,255,.08);border-radius:12px}</style></head>'
           . '<body><div class="b"><h2>403 — Accesso non consentito</h2>'
           . '<p>Questa pagina è accessibile solo dalla rete locale.</p></div></body></html>');
    }

    if (isset($_GET['logout'])) {
        admin_clear_session();
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }

    $authError = '';
    if (!$isLocalhost && isset($_POST['_lan_pwd'])) {
        $username = trim($_POST['_lan_user'] ?? 'admin');
        $password = trim($_POST['_lan_pwd'] ?? '');
        $user = admin_find_user_by_username($username);
        if ($user && ($user['active'] ?? false) && password_verify($password, $user['password_hash'] ?? '')) {
            admin_set_session_user($user, $remoteIP);
            $landing = admin_first_allowed_page($user);
            if ($landing && !admin_user_can_access($user, $pageKey)) {
                header('Location: ' . $landing);
            } else {
                header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
            }
            exit;
        }
        $authError = 'Utente o password non corretti.';
        sleep(1);
    }

    $sessionOk = $isLocalhost || admin_session_valid($remoteIP);
    if (!$isLocalhost && !$sessionOk) {
        admin_render_login($loginTitle, $loginSubtitle, $authError, admin_using_default_password());
    }

    $currentUser = admin_session_user();
    if (!$isLocalhost && $currentUser && !admin_user_can_access($currentUser, $pageKey)) {
        $landing = admin_first_allowed_page($currentUser);
        if ($landing && admin_current_script() !== $landing) {
            header('Location: ' . $landing);
            exit;
        }
        http_response_code(403);
        die('<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><title>Accesso negato</title>'
           . '<style>body{font-family:Segoe UI,sans-serif;background:#0d1b3e;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}'
           . '.b{text-align:center;padding:40px;background:rgba(255,255,255,.08);border-radius:12px}</style></head>'
           . '<body><div class="b"><h2>403 — Permesso insufficiente</h2>'
           . '<p>Il tuo account non ha accesso a nessuna sezione del pannello.</p></div></body></html>');
    }

    $GLOBALS['admin_remote_ip'] = $remoteIP;
    $GLOBALS['admin_is_localhost'] = $isLocalhost;
    $GLOBALS['admin_session_ok'] = $sessionOk;
    $GLOBALS['admin_current_user'] = $currentUser;
}

function admin_render_sidebar(string $activePage, string $logoHtml = '&#9881;&#65039; <span style="font-size:18px">Config</span>'): void
{
    $isLocalhost = $GLOBALS['admin_is_localhost'] ?? false;
    $currentUser = $GLOBALS['admin_current_user'] ?? null;

    $groups = [];
    foreach (ADMIN_PAGES as $key => $page) {
        if ($key === 'stampa_errori') {
            continue;
        }
        if (!$isLocalhost && $currentUser && !admin_user_can_access($currentUser, $key)) {
            continue;
        }
        $groups[$page['group']][] = ['key' => $key] + $page;
    }

    echo '<nav class="sidebar">';
    echo '<div class="sidebar-logo">' . $logoHtml . '</div>';

    $groupLabels = ['Totem' => 'Totem', 'Monitor' => 'Monitor', 'Sistema' => 'Sistema'];
    $first = true;
    foreach ($groupLabels as $groupName => $label) {
        if (empty($groups[$groupName])) {
            continue;
        }
        if (!$first) {
            echo '<div class="sidebar-sep"></div>';
        }
        echo '<h2>' . htmlspecialchars($label) . '</h2>';
        foreach ($groups[$groupName] as $page) {
            $cls = ($page['key'] === $activePage) ? ' class="active"' : '';
            $target = !empty($page['target']) ? ' target="' . htmlspecialchars($page['target']) . '"' : '';
            echo '<a href="' . htmlspecialchars($page['file']) . '"' . $cls . $target . '>'
               . $page['icon'] . ' ' . htmlspecialchars($page['label']) . '</a>';
        }
        $first = false;
    }

    echo '<div class="sidebar-sep"></div>';
    echo '<a href="totem.php" target="_blank">&#128065; Anteprima Totem</a>';
    echo '<a href="index2.php" target="_blank">&#128065; Anteprima Monitor</a>';
    if ($isLocalhost || ($currentUser && admin_user_can_access($currentUser, 'stampa_errori'))) {
        echo '<a href="stampa_errori_view.php" target="_blank">&#128203; Log Stampa</a>';
    }

    if (!$isLocalhost) {
        echo '<div class="sidebar-sep"></div>';
        if ($currentUser) {
            echo '<div style="padding:8px 14px;font-size:11px;opacity:.55;color:#fff">'
               . htmlspecialchars($currentUser['name'] ?? $currentUser['username'] ?? '') . '</div>';
        }
        echo '<a href="?logout=1">&#128275; Esci</a>';
    }
    echo '</nav>';
}

function admin_update_user_password(string $userId, string $newPassword): bool
{
    $store = admin_load_users_store();
    foreach ($store['users'] as &$user) {
        if (($user['id'] ?? '') === $userId) {
            $user['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            return admin_save_users_store($store);
        }
    }
    return false;
}
