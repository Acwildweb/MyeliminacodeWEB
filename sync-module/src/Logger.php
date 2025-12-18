<?php
/**
 * Logger - Sistema di logging
 */

namespace MySanitario\Sync;

class Logger {
    private static ?Logger $instance = null;
    private string $logFile;
    private bool $enabled;
    private string $level;
    
    private const LEVELS = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3
    ];
    
    private function __construct() {
        $this->enabled = defined('ENABLE_LOGGING') ? ENABLE_LOGGING : true;
        $this->level = defined('LOG_LEVEL') ? LOG_LEVEL : 'INFO';
        
        $logsPath = defined('LOGS_PATH') ? LOGS_PATH : dirname(__DIR__) . '/logs';
        
        if (!is_dir($logsPath)) {
            mkdir($logsPath, 0755, true);
        }
        
        $this->logFile = $logsPath . '/sync_' . date('Y-m-d') . '.log';
    }
    
    public static function getInstance(): Logger {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function shouldLog(string $level): bool {
        if (!$this->enabled) {
            return false;
        }
        
        $currentLevel = self::LEVELS[$this->level] ?? 1;
        $messageLevel = self::LEVELS[$level] ?? 1;
        
        return $messageLevel >= $currentLevel;
    }
    
    private function write(string $level, string $message, array $context = []): void {
        if (!$this->shouldLog($level)) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logLine = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    public function debug(string $message, array $context = []): void {
        $this->write('DEBUG', $message, $context);
    }
    
    public function info(string $message, array $context = []): void {
        $this->write('INFO', $message, $context);
    }
    
    public function warning(string $message, array $context = []): void {
        $this->write('WARNING', $message, $context);
    }
    
    public function error(string $message, array $context = []): void {
        $this->write('ERROR', $message, $context);
    }
    
    /**
     * Legge le ultime N righe del log
     */
    public function getLastLines(int $lines = 100): array {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $content = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice($content, -$lines);
    }
    
    /**
     * Pulisce i log più vecchi di N giorni
     */
    public function cleanOldLogs(int $days = 30): int {
        $logsPath = dirname($this->logFile);
        $count = 0;
        $cutoff = time() - ($days * 86400);
        
        foreach (glob($logsPath . '/sync_*.log') as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $count++;
            }
        }
        
        return $count;
    }
}
