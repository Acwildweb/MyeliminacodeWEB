<?php
/**
 * gestione_utenti.php — CRUD utenti e permessi (system_users.json, no DB)
 */
require_once __DIR__ . '/admin_auth_lib.php';
admin_require_page('gestione_utenti', 'Gestione utenti', 'Accesso riservato agli amministratori');

$isLocalhost = $GLOBALS['admin_is_localhost'];
$currentUser = $GLOBALS['admin_current_user'];
$message = '';
$msgType = '';

function gu_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function gu_count_superadmins(array $store): int
{
    $n = 0;
    foreach ($store['users'] as $user) {
        if (!empty($user['is_superadmin']) && ($user['active'] ?? false)) {
            $n++;
        }
    }
    return $n;
}

function gu_parse_pages_from_post(): array
{
    $pages = [];
    foreach (admin_all_page_keys() as $key) {
        $pages[$key] = isset($_POST['pages'][$key]);
    }
    return $pages;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $store = admin_load_users_store();

    if ($action === 'create') {
        $username = strtolower(trim($_POST['username'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $isSuper = isset($_POST['is_superadmin']);
        $pages = gu_parse_pages_from_post();

        if ($username === '' || !preg_match('/^[a-z0-9._-]{3,32}$/', $username)) {
            $message = 'Username non valido (3-32 caratteri: lettere, numeri, . _ -).';
            $msgType = 'error';
        } elseif (admin_find_user_by_username($username)) {
            $message = 'Username già esistente.';
            $msgType = 'error';
        } elseif (strlen($password) < 6) {
            $message = 'La password deve essere di almeno 6 caratteri.';
            $msgType = 'error';
        } else {
            $store['users'][] = [
                'id' => 'u_' . bin2hex(random_bytes(8)),
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'name' => $name !== '' ? $name : $username,
                'active' => true,
                'is_superadmin' => $isSuper,
                'pages' => $isSuper ? admin_default_pages(true) : $pages,
            ];
            if (admin_save_users_store($store)) {
                $message = 'Utente creato con successo.';
                $msgType = 'success';
            } else {
                $message = 'Impossibile salvare il file utenti.';
                $msgType = 'error';
            }
        }
    }

    if ($action === 'update') {
        $userId = trim($_POST['user_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $active = isset($_POST['active']);
        $isSuper = isset($_POST['is_superadmin']);
        $pages = gu_parse_pages_from_post();
        $found = false;

        foreach ($store['users'] as &$user) {
            if (($user['id'] ?? '') !== $userId) {
                continue;
            }
            $found = true;

            if (!$active && !empty($user['is_superadmin']) && gu_count_superadmins($store) <= 1) {
                $message = 'Impossibile disattivare l\'ultimo super-amministratore.';
                $msgType = 'error';
                break;
            }

            $user['name'] = $name !== '' ? $name : ($user['username'] ?? '');
            $user['active'] = $active;
            $user['is_superadmin'] = $isSuper;
            $user['pages'] = $isSuper ? admin_default_pages(true) : $pages;

            if ($password !== '') {
                if (strlen($password) < 6) {
                    $message = 'La password deve essere di almeno 6 caratteri.';
                    $msgType = 'error';
                    break;
                }
                $user['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }

            if (admin_save_users_store($store)) {
                $message = 'Utente aggiornato.';
                $msgType = 'success';
            } else {
                $message = 'Impossibile salvare il file utenti.';
                $msgType = 'error';
            }
            break;
        }

        if (!$found && $message === '') {
            $message = 'Utente non trovato.';
            $msgType = 'error';
        }
    }

    if ($action === 'delete') {
        $userId = trim($_POST['user_id'] ?? '');
        if ($currentUser && ($currentUser['id'] ?? '') === $userId) {
            $message = 'Non puoi eliminare il tuo account mentre sei connesso.';
            $msgType = 'error';
        } else {
            $target = admin_find_user_by_id($userId);
            if (!$target) {
                $message = 'Utente non trovato.';
                $msgType = 'error';
            } elseif (!empty($target['is_superadmin']) && gu_count_superadmins($store) <= 1) {
                $message = 'Impossibile eliminare l\'ultimo super-amministratore.';
                $msgType = 'error';
            } else {
                $store['users'] = array_values(array_filter(
                    $store['users'],
                    static fn($u) => ($u['id'] ?? '') !== $userId
                ));
                if (admin_save_users_store($store)) {
                    $message = 'Utente eliminato.';
                    $msgType = 'success';
                } else {
                    $message = 'Impossibile salvare il file utenti.';
                    $msgType = 'error';
                }
            }
        }
    }
}

$store = admin_load_users_store();
$users = $store['users'];
$editId = $_GET['edit'] ?? '';
$editUser = $editId !== '' ? admin_find_user_by_id($editId) : null;
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestione utenti</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f0f2f5;color:#333;min-height:100vh}
.page{display:flex;min-height:100vh}
.sidebar{width:260px;flex:0 0 260px;background:linear-gradient(160deg,#0d1b3e 0%,#1a3a6e 100%);color:#fff;padding:24px 0;position:sticky;top:0;height:100vh;overflow-y:auto}
.sidebar h2{font-size:15px;font-weight:800;letter-spacing:.05em;margin-bottom:16px;padding:0 14px;color:rgba(255,255,255,.45)}
.sidebar a{display:flex;align-items:center;gap:10px;padding:10px 14px;color:rgba(255,255,255,.75);text-decoration:none;font-size:14px;border-radius:8px;margin:2px 8px}
.sidebar a:hover{background:rgba(255,255,255,.1);color:#fff}
.sidebar a.active{background:rgba(66,165,245,.25);color:#42a5f5;font-weight:700}
.sidebar-sep{height:1px;background:rgba(255,255,255,.1);margin:8px 16px}
.sidebar-logo{font-size:28px;font-weight:900;color:#42a5f5;margin-bottom:24px;padding:0 14px}
.main{flex:1;display:flex;flex-direction:column;min-width:0}
.topbar{background:#fff;border-bottom:1px solid #e0e0e0;padding:18px 28px;display:flex;align-items:center;justify-content:space-between;gap:16px}
.topbar h1{font-size:22px;font-weight:700}
.content{padding:24px 28px 40px;max-width:1100px}
.alert{padding:12px 16px;border-radius:8px;margin-bottom:18px;font-size:14px}
.alert-success{background:#e6f4ea;color:#137333;border:1px solid #ceead6}
.alert-error{background:#fce8e6;color:#c5221f;border:1px solid #f5c6c2}
.card{background:#fff;border-radius:12px;padding:22px 24px;margin-bottom:20px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.card-title{font-size:16px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px}
table{width:100%;border-collapse:collapse;font-size:14px}
th,td{padding:10px 12px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}
th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#666}
.badge{display:inline-block;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700}
.badge-ok{background:#e6f4ea;color:#137333}
.badge-off{background:#f1f3f4;color:#666}
.badge-sa{background:#e8f0fe;color:#1a73e8}
.lbl{display:block;font-size:12px;font-weight:700;color:#555;margin:12px 0 6px}
input[type=text],input[type=password]{width:100%;padding:10px 12px;border:1px solid #dde3ec;border-radius:8px;font-size:14px}
.perm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px 16px;margin-top:8px}
.perm-item{display:flex;align-items:center;gap:8px;font-size:13px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;border:none;text-decoration:none}
.btn-primary{background:#1a73e8;color:#fff}
.btn-primary:hover{background:#1558b0}
.btn-ghost{background:transparent;color:#1a73e8;border:2px solid #1a73e8}
.btn-danger{background:#ea4335;color:#fff}
.btn-sm{padding:6px 12px;font-size:12px}
.note{font-size:12px;color:#777;line-height:1.5}
.actions{display:flex;gap:8px;flex-wrap:wrap}
</style>
</head>
<body>
<div class="page">
<?php admin_render_sidebar('gestione_utenti'); ?>
<div class="main">
<div class="topbar">
    <h1>&#128101; Gestione utenti</h1>
    <a href="gestione_utenti.php" class="btn btn-ghost btn-sm">+ Nuovo utente</a>
</div>
<div class="content">
<?php if ($message): ?>
<div class="alert alert-<?php echo $msgType === 'success' ? 'success' : 'error'; ?>"><?php echo gu_h($message); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-title"><span>&#128203;</span> Utenti configurati</div>
    <p class="note" style="margin-bottom:14px">Account salvati in <code>system_users.json</code> (non nel database). Ogni utente ha accesso solo alle sezioni abilitate.</p>
    <table>
        <thead>
            <tr>
                <th>Utente</th>
                <th>Nome</th>
                <th>Stato</th>
                <th>Permessi</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><strong><?php echo gu_h($user['username'] ?? ''); ?></strong>
                    <?php if (!empty($user['is_superadmin'])): ?><span class="badge badge-sa">Super-admin</span><?php endif; ?>
                </td>
                <td><?php echo gu_h($user['name'] ?? ''); ?></td>
                <td>
                    <?php if ($user['active'] ?? false): ?>
                    <span class="badge badge-ok">Attivo</span>
                    <?php else: ?>
                    <span class="badge badge-off">Disattivo</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    if (!empty($user['is_superadmin'])) {
                        echo 'Tutte le sezioni';
                    } else {
                        $labels = [];
                        foreach (ADMIN_PAGES as $key => $page) {
                            if (!empty($user['pages'][$key])) {
                                $labels[] = $page['label'];
                            }
                        }
                        echo gu_h($labels ? implode(', ', $labels) : 'Nessuno');
                    }
                    ?>
                </td>
                <td class="actions">
                    <a class="btn btn-ghost btn-sm" href="gestione_utenti.php?edit=<?php echo urlencode($user['id']); ?>">Modifica</a>
                    <?php if (($user['id'] ?? '') !== ($currentUser['id'] ?? '')): ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('Eliminare questo utente?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?php echo gu_h($user['id']); ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Elimina</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-title"><span><?php echo $editUser ? '&#9998;' : '&#10133;'; ?></span> <?php echo $editUser ? 'Modifica utente' : 'Nuovo utente'; ?></div>
    <form method="post">
        <input type="hidden" name="action" value="<?php echo $editUser ? 'update' : 'create'; ?>">
        <?php if ($editUser): ?>
        <input type="hidden" name="user_id" value="<?php echo gu_h($editUser['id']); ?>">
        <?php else: ?>
        <label class="lbl" for="username">Username</label>
        <input type="text" id="username" name="username" required pattern="[a-z0-9._-]{3,32}" placeholder="es. operatore1">
        <?php endif; ?>

        <label class="lbl" for="name">Nome visualizzato</label>
        <input type="text" id="name" name="name" value="<?php echo gu_h($editUser['name'] ?? ''); ?>" placeholder="Es. Mario Rossi">

        <label class="lbl" for="password">Password <?php echo $editUser ? '(lascia vuoto per non cambiare)' : ''; ?></label>
        <input type="password" id="password" name="password" <?php echo $editUser ? '' : 'required'; ?> minlength="6" autocomplete="new-password">

        <div class="perm-item" style="margin-top:14px">
            <input type="checkbox" id="active" name="active" value="1" <?php echo (!$editUser || ($editUser['active'] ?? false)) ? 'checked' : ''; ?>>
            <label for="active">Account attivo</label>
        </div>
        <div class="perm-item" style="margin-top:8px">
            <input type="checkbox" id="is_superadmin" name="is_superadmin" value="1"
                <?php echo ($editUser['is_superadmin'] ?? false) ? 'checked' : ''; ?>
                onchange="document.getElementById('perm-grid').style.opacity=this.checked?0.45:1">
            <label for="is_superadmin">Super-amministratore (accesso completo)</label>
        </div>

        <label class="lbl">Permessi per sezione</label>
        <div class="perm-grid" id="perm-grid" style="<?php echo ($editUser['is_superadmin'] ?? false) ? 'opacity:.45' : ''; ?>">
            <?php foreach (ADMIN_PAGES as $key => $page): ?>
            <label class="perm-item">
                <input type="checkbox" name="pages[<?php echo gu_h($key); ?>]" value="1"
                    <?php echo (!$editUser || !empty($editUser['pages'][$key]) || !empty($editUser['is_superadmin'])) ? 'checked' : ''; ?>>
                <?php echo $page['icon'] . ' ' . gu_h($page['label']); ?>
            </label>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:18px;display:flex;gap:10px">
            <button type="submit" class="btn btn-primary"><?php echo $editUser ? 'Salva modifiche' : 'Crea utente'; ?></button>
            <?php if ($editUser): ?>
            <a href="gestione_utenti.php" class="btn btn-ghost">Annulla</a>
            <?php endif; ?>
        </div>
    </form>
</div>

</div>
</div>
</div>
</body>
</html>
