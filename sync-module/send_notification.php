<?php
/**
 * Script per invio notifiche programmate
 * Da eseguire ogni 5 minuti tramite Task Scheduler
 * 
 * Utilizzo:
 *   php send_notification.php           # Invia se è il momento programmato
 *   php send_notification.php --force   # Forza invio immediato
 */

require_once __DIR__ . '/bootstrap.php';

use MySanitario\Sync\NotificationScheduler;
use MySanitario\Sync\EmailService;

$force = in_array('--force', $argv ?? []);

echo "=== MySanitario - Notifiche Email ===\n";
echo "Data/Ora: " . date('Y-m-d H:i:s') . "\n";

$scheduler = new NotificationScheduler();
$config = $scheduler->getConfig();

echo "Notifiche abilitate: " . ($config['enabled'] ? 'SI' : 'NO') . "\n";
echo "Email destinatario: " . ($config['email_to'] ?: 'Non configurato') . "\n";

if ($force) {
    echo "\n[FORCE] Invio immediato...\n";
    $result = $scheduler->sendNow();
} else {
    if ($scheduler->shouldSendNow()) {
        echo "\n[SCHEDULED] È il momento di inviare la notifica...\n";
        $result = $scheduler->sendScheduledNotification();
    } else {
        echo "\nNon è il momento programmato per l'invio.\n";
        echo "Orario programmato: " . $config['schedule']['time'] . "\n";
        echo "Giorni: " . implode(', ', array_map(function($d) {
            $days = ['', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
            return $days[$d] ?? $d;
        }, $config['schedule']['days'])) . "\n";
        exit(0);
    }
}

if ($result['success']) {
    echo "✓ " . $result['message'] . "\n";
    exit(0);
} else {
    echo "✗ " . $result['message'] . "\n";
    exit(1);
}
