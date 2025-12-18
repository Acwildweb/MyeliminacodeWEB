<?php
/**
 * Autoloader e bootstrap del modulo
 */

define('SYNC_MODULE', true);

// Carica configurazione
require_once __DIR__ . '/config/config.php';

// Autoloader classi
spl_autoload_register(function ($class) {
    // Namespace base
    $prefix = 'MySanitario\\Sync\\';
    $base_dir = __DIR__ . '/src/';
    
    // Verifica se la classe usa il namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Ottieni il nome relativo della classe
    $relative_class = substr($class, $len);
    
    // Costruisci il path del file
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // Carica il file se esiste
    if (file_exists($file)) {
        require $file;
    }
});
