<?php
/**
 * Configurazione locale - MODIFICA QUESTI VALORI
 * Questo file contiene le impostazioni specifiche dell'installazione
 */

// === CONFIGURAZIONE DATABASE LOCALE ===
define('DB_HOST', 'localhost');
define('DB_USER', 'mysanitario');
define('DB_PASSWORD', 'mysanitario2025');
define('DB_NAME', 'mysanitario');
define('DB_CHARSET', 'utf8mb4');

// === ID CLIENTE/MONITOR ===
// Questo è l'ID univoco del tuo monitor/cliente sul server online
// Lo trovi nel pannello di amministrazione online
define('CLIENT_ID', '');  // Es: 'Monitor-xzK0K'

// === PERCORSI STORAGE ===
// Dove salvare immagini e video scaricati
// Lascia vuoto per usare i percorsi default nella cartella storage/
define('CUSTOM_IMAGES_PATH', '');  // Es: 'C:/Users/Public/immagini/'
define('CUSTOM_VIDEOS_PATH', '');  // Es: 'C:/Users/Public/video/'

// === OPZIONI SINCRONIZZAZIONE ===
// Intervallo sincronizzazione automatica (in minuti)
define('SYNC_INTERVAL', 15);

// Sincronizza anche file multimediali (immagini/video)
define('SYNC_MEDIA_FILES', true);

// Elimina file locali non più presenti sul server
define('DELETE_ORPHAN_FILES', false);

// === LOG ===
define('ENABLE_LOGGING', true);
define('LOG_LEVEL', 'INFO');  // DEBUG, INFO, WARNING, ERROR
