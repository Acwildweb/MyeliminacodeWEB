<?php
/**
 * Pagina Configurazione Email e Notifiche
 * MySanitario Sync Module
 */

require_once __DIR__ . '/bootstrap.php';

use MySanitario\Sync\EmailService;
use MySanitario\Sync\NotificationScheduler;
use MySanitario\Sync\Logger;

$message = '';
$messageType = '';

$emailService = new EmailService();
$scheduler = new NotificationScheduler();
$notifConfig = $scheduler->getConfig();

// Gestione azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_smtp':
            // Leggi configurazione esistente
            $localConfigFile = __DIR__ . '/config/local.php';
            $existingContent = file_exists($localConfigFile) ? file_get_contents($localConfigFile) : '';
            
            // Rimuovi vecchie configurazioni SMTP se presenti
            $existingContent = preg_replace('/\/\/ === CONFIGURAZIONE SMTP ===[^§]*?(?=\/\/ ===|$)/s', '', $existingContent);
            $existingContent = rtrim($existingContent);
            
            // Aggiungi nuova configurazione SMTP
            $smtpConfig = "\n\n// === CONFIGURAZIONE SMTP ===\n";
            $smtpConfig .= "define('SMTP_HOST', '" . addslashes($_POST['smtp_host'] ?? '') . "');\n";
            $smtpConfig .= "define('SMTP_PORT', " . intval($_POST['smtp_port'] ?? 587) . ");\n";
            $smtpConfig .= "define('SMTP_USER', '" . addslashes($_POST['smtp_user'] ?? '') . "');\n";
            $smtpConfig .= "define('SMTP_PASSWORD', '" . addslashes($_POST['smtp_password'] ?? '') . "');\n";
            $smtpConfig .= "define('SMTP_ENCRYPTION', '" . addslashes($_POST['smtp_encryption'] ?? 'tls') . "');\n";
            $smtpConfig .= "define('EMAIL_FROM', '" . addslashes($_POST['email_from'] ?? '') . "');\n";
            $smtpConfig .= "define('EMAIL_FROM_NAME', '" . addslashes($_POST['email_from_name'] ?? 'MySanitario Sync') . "');\n";
            
            if (file_put_contents($localConfigFile, $existingContent . $smtpConfig)) {
                $message = 'Configurazione SMTP salvata con successo!';
                $messageType = 'success';
            } else {
                $message = 'Errore nel salvataggio della configurazione SMTP';
                $messageType = 'error';
            }
            break;
            
        case 'test_smtp':
            $result = $emailService->testConnection();
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            if ($result['success'] && !empty($result['details']['server_response'])) {
                $message .= ' - ' . substr($result['details']['server_response'], 0, 50);
            }
            break;
            
        case 'send_test_email':
            $testEmail = $_POST['test_email'] ?? '';
            if (empty($testEmail)) {
                $message = 'Inserisci un indirizzo email per il test';
                $messageType = 'error';
            } elseif (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                $message = 'Indirizzo email non valido';
                $messageType = 'error';
            } else {
                if ($emailService->sendTestEmail($testEmail)) {
                    $message = 'Email di test inviata a ' . $testEmail;
                    $messageType = 'success';
                } else {
                    $message = 'Errore invio: ' . $emailService->getLastError();
                    $messageType = 'error';
                }
            }
            break;
            
        case 'save_notifications':
            $notifConfig['enabled'] = isset($_POST['notif_enabled']);
            $notifConfig['email_to'] = $_POST['notif_email'] ?? '';
            $notifConfig['schedule']['time'] = $_POST['notif_time'] ?? '08:00';
            $notifConfig['schedule']['days'] = array_map('intval', $_POST['notif_days'] ?? []);
            $notifConfig['include_errors'] = isset($_POST['include_errors']);
            $notifConfig['include_warnings'] = isset($_POST['include_warnings']);
            $notifConfig['include_success'] = isset($_POST['include_success']);
            
            if ($scheduler->saveConfig($notifConfig)) {
                $message = 'Configurazione notifiche salvata!';
                $messageType = 'success';
                $notifConfig = $scheduler->getConfig(); // Ricarica
            } else {
                $message = 'Errore nel salvataggio';
                $messageType = 'error';
            }
            break;
            
        case 'send_report_now':
            $result = $scheduler->sendNow();
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;
    }
}

// Carica configurazione SMTP attuale
$smtpConfig = [
    'host' => defined('SMTP_HOST') ? SMTP_HOST : '',
    'port' => defined('SMTP_PORT') ? SMTP_PORT : 587,
    'user' => defined('SMTP_USER') ? SMTP_USER : '',
    'password' => defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '',
    'encryption' => defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls',
    'from' => defined('EMAIL_FROM') ? EMAIL_FROM : '',
    'from_name' => defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'MySanitario Sync',
];

$days = NotificationScheduler::getDaysList();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySanitario Sync - Configurazione Email</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .nav-links {
            margin-top: 15px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 13px;
            margin: 0 5px;
            transition: background 0.2s;
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-header .icon {
            font-size: 20px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group input[type="time"],
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-row-3 {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 15px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 8px 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .days-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        
        .days-selector label {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .days-selector label:hover {
            background: #e9ecef;
        }
        
        .days-selector input:checked + span {
            color: #667eea;
            font-weight: 600;
        }
        
        .days-selector label:has(input:checked) {
            border-color: #667eea;
            background: #f0f3ff;
        }
        
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b6d4fe;
            color: #084298;
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 15px;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-badge.configured {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.not-configured {
            background: #f8d7da;
            color: #721c24;
        }
        
        .section-title {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        
        hr {
            border: none;
            border-top: 1px solid #eee;
            margin: 20px 0;
        }
        
        small {
            color: #888;
            font-size: 12px;
        }
        
        @media (max-width: 600px) {
            .form-row, .form-row-3 {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .days-selector {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📧 Configurazione Email</h1>
            <p>Impostazioni SMTP e Notifiche Programmate</p>
            <div class="nav-links">
                <a href="index.php">← Pannello Principale</a>
            </div>
        </header>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= $messageType === 'success' ? '✓' : '✗' ?>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        
        <!-- Stato Configurazione -->
        <div class="card">
            <div class="card-header">
                <span class="icon">📊</span>
                Stato Configurazione
            </div>
            <div class="card-body">
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div>
                        <strong>SMTP:</strong>
                        <?php if ($emailService->isConfigured()): ?>
                            <span class="status-badge configured">✓ Configurato</span>
                        <?php else: ?>
                            <span class="status-badge not-configured">✗ Non configurato</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <strong>Notifiche:</strong>
                        <?php if ($notifConfig['enabled']): ?>
                            <span class="status-badge configured">✓ Attive</span>
                        <?php else: ?>
                            <span class="status-badge not-configured">○ Disattivate</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($notifConfig['last_sent']): ?>
                    <div>
                        <strong>Ultimo invio:</strong>
                        <code><?= htmlspecialchars($notifConfig['last_sent']) ?></code>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Configurazione SMTP -->
        <div class="card">
            <div class="card-header">
                <span class="icon">📤</span>
                Configurazione SMTP
            </div>
            <div class="card-body">
                <div class="info-box">
                    <strong>ℹ️ Info:</strong> Configura il server SMTP per l'invio delle email. 
                    Puoi usare Gmail, Outlook, o qualsiasi altro provider SMTP.
                </div>
                
                <form method="post">
                    <input type="hidden" name="action" value="save_smtp">
                    
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Server SMTP</label>
                            <input type="text" name="smtp_host" value="<?= htmlspecialchars($smtpConfig['host']) ?>" placeholder="smtp.gmail.com">
                        </div>
                        <div class="form-group">
                            <label>Porta</label>
                            <input type="number" name="smtp_port" value="<?= htmlspecialchars($smtpConfig['port']) ?>" placeholder="587">
                        </div>
                        <div class="form-group">
                            <label>Crittografia</label>
                            <select name="smtp_encryption">
                                <option value="tls" <?= $smtpConfig['encryption'] === 'tls' ? 'selected' : '' ?>>TLS (porta 587)</option>
                                <option value="ssl" <?= $smtpConfig['encryption'] === 'ssl' ? 'selected' : '' ?>>SSL (porta 465)</option>
                                <option value="none" <?= $smtpConfig['encryption'] === 'none' ? 'selected' : '' ?>>Nessuna</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Username / Email</label>
                            <input type="text" name="smtp_user" value="<?= htmlspecialchars($smtpConfig['user']) ?>" placeholder="tuaemail@gmail.com">
                        </div>
                        <div class="form-group">
                            <label>Password / App Password</label>
                            <input type="password" name="smtp_password" value="<?= htmlspecialchars($smtpConfig['password']) ?>" placeholder="••••••••">
                            <small>Per Gmail, usa una "Password per le app"</small>
                        </div>
                    </div>
                    
                    <div class="section-title">Mittente Email</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email Mittente</label>
                            <input type="email" name="email_from" value="<?= htmlspecialchars($smtpConfig['from']) ?>" placeholder="noreply@tuodominio.com">
                            <small>Lascia vuoto per usare lo username SMTP</small>
                        </div>
                        <div class="form-group">
                            <label>Nome Mittente</label>
                            <input type="text" name="email_from_name" value="<?= htmlspecialchars($smtpConfig['from_name']) ?>" placeholder="MySanitario Sync">
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">💾 Salva Configurazione SMTP</button>
                    </div>
                </form>
                
                <hr>
                
                <div class="section-title">Test Connessione</div>
                
                <div class="btn-group" style="margin-top: 10px;">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="test_smtp">
                        <button type="submit" class="btn btn-secondary">🔌 Test Connessione SMTP</button>
                    </form>
                </div>
                
                <div class="section-title" style="margin-top: 20px;">Invia Email di Test</div>
                
                <form method="post" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                    <input type="hidden" name="action" value="send_test_email">
                    <div class="form-group" style="flex: 1; min-width: 200px; margin: 0;">
                        <input type="email" name="test_email" placeholder="test@esempio.com" style="margin: 0;">
                    </div>
                    <button type="submit" class="btn btn-success">📧 Invia Test</button>
                </form>
            </div>
        </div>
        
        <!-- Configurazione Notifiche Programmate -->
        <div class="card">
            <div class="card-header">
                <span class="icon">🔔</span>
                Notifiche Programmate
            </div>
            <div class="card-body">
                <div class="info-box">
                    <strong>ℹ️ Info:</strong> Configura l'invio automatico di report via email negli orari e giorni selezionati.
                    Il sistema invierà un riepilogo delle operazioni di sincronizzazione.
                </div>
                
                <form method="post">
                    <input type="hidden" name="action" value="save_notifications">
                    
                    <div class="checkbox-group" style="margin-bottom: 20px;">
                        <input type="checkbox" name="notif_enabled" id="notif_enabled" <?= $notifConfig['enabled'] ? 'checked' : '' ?>>
                        <label for="notif_enabled" style="margin: 0; font-weight: 600; font-size: 16px;">Abilita notifiche email</label>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email Destinatario</label>
                            <input type="email" name="notif_email" value="<?= htmlspecialchars($notifConfig['email_to']) ?>" placeholder="admin@esempio.com">
                        </div>
                        <div class="form-group">
                            <label>Orario Invio</label>
                            <input type="time" name="notif_time" value="<?= htmlspecialchars($notifConfig['schedule']['time']) ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Giorni della Settimana</label>
                        <div class="days-selector">
                            <?php foreach ($days as $num => $name): ?>
                            <label>
                                <input type="checkbox" name="notif_days[]" value="<?= $num ?>" 
                                    <?= in_array($num, $notifConfig['schedule']['days']) ? 'checked' : '' ?>>
                                <span><?= $name ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="section-title">Contenuto Report</div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="include_success" id="include_success" <?= $notifConfig['include_success'] ? 'checked' : '' ?>>
                        <label for="include_success">✅ Includi operazioni completate con successo</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" name="include_warnings" id="include_warnings" <?= $notifConfig['include_warnings'] ? 'checked' : '' ?>>
                        <label for="include_warnings">⚠️ Includi avvisi (warning)</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" name="include_errors" id="include_errors" <?= $notifConfig['include_errors'] ? 'checked' : '' ?>>
                        <label for="include_errors">❌ Includi errori</label>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">💾 Salva Configurazione Notifiche</button>
                    </div>
                </form>
                
                <hr>
                
                <div class="section-title">Invio Manuale</div>
                
                <form method="post">
                    <input type="hidden" name="action" value="send_report_now">
                    <button type="submit" class="btn btn-success">📨 Invia Report Adesso</button>
                </form>
                <small>Invia immediatamente un report con i log delle ultime 24 ore</small>
            </div>
        </div>
        
        <!-- Istruzioni Task Scheduler -->
        <div class="card">
            <div class="card-header">
                <span class="icon">⏰</span>
                Configurazione Task Scheduler
            </div>
            <div class="card-body">
                <p style="margin-bottom: 15px;">
                    Per abilitare l'invio automatico delle notifiche, devi configurare un'attività pianificata che esegua 
                    lo script <code>send_notification.bat</code> ogni 5 minuti.
                </p>
                
                <div class="info-box">
                    <strong>Script da schedulare:</strong><br>
                    <code>C:\xampp\htdocs\sync-module\scripts\send_notification.bat</code>
                </div>
                
                <p>Lo script verificherà automaticamente se è il momento di inviare la notifica in base alla configurazione sopra.</p>
                
                <div class="btn-group">
                    <a href="MANUALE_INSTALLAZIONE.md" class="btn btn-secondary" target="_blank">📖 Leggi Manuale Completo</a>
                </div>
            </div>
        </div>
        
        <footer style="text-align: center; color: #888; font-size: 12px; margin-top: 30px;">
            MySanitario Sync Module v<?= defined('SYNC_VERSION') ? SYNC_VERSION : '1.0.0' ?> | 
            <a href="index.php" style="color: #667eea;">← Torna al Pannello</a>
        </footer>
    </div>
</body>
</html>
