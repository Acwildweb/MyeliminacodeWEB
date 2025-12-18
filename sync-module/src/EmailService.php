<?php
/**
 * EmailService - Servizio di invio email tramite SMTP
 */

namespace MySanitario\Sync;

class EmailService {
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUser;
    private string $smtpPassword;
    private string $smtpEncryption; // 'tls', 'ssl', 'none'
    private string $fromEmail;
    private string $fromName;
    private Logger $logger;
    
    private ?string $lastError = null;
    
    public function __construct() {
        $this->logger = Logger::getInstance();
        $this->loadConfig();
    }
    
    /**
     * Carica configurazione SMTP
     */
    private function loadConfig(): void {
        $this->smtpHost = defined('SMTP_HOST') ? SMTP_HOST : '';
        $this->smtpPort = defined('SMTP_PORT') ? (int)SMTP_PORT : 587;
        $this->smtpUser = defined('SMTP_USER') ? SMTP_USER : '';
        $this->smtpPassword = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
        $this->smtpEncryption = defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls';
        $this->fromEmail = defined('EMAIL_FROM') ? EMAIL_FROM : $this->smtpUser;
        $this->fromName = defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'MySanitario Sync';
    }
    
    /**
     * Verifica se la configurazione SMTP è completa
     */
    public function isConfigured(): bool {
        return !empty($this->smtpHost) && !empty($this->smtpUser);
    }
    
    /**
     * Invia email tramite SMTP usando socket
     */
    public function send(string $to, string $subject, string $body, bool $isHtml = true): bool {
        if (!$this->isConfigured()) {
            $this->lastError = 'Configurazione SMTP incompleta';
            return false;
        }
        
        try {
            // Usa fsockopen per SMTP
            $result = $this->sendViaSMTP($to, $subject, $body, $isHtml);
            
            if ($result) {
                $this->logger->info('Email inviata', ['to' => $to, 'subject' => $subject]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logger->error('Errore invio email', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Invio email via SMTP socket
     */
    private function sendViaSMTP(string $to, string $subject, string $body, bool $isHtml): bool {
        $port = $this->smtpPort;
        $host = $this->smtpHost;
        
        // SSL/TLS wrapper
        if ($this->smtpEncryption === 'ssl') {
            $host = 'ssl://' . $host;
        }
        
        // Connessione
        $socket = @fsockopen($host, $port, $errno, $errstr, 30);
        if (!$socket) {
            $this->lastError = "Connessione fallita: $errstr ($errno)";
            return false;
        }
        
        // Timeout
        stream_set_timeout($socket, 30);
        
        // Leggi risposta iniziale
        $response = $this->getResponse($socket);
        if (substr($response, 0, 3) !== '220') {
            $this->lastError = "Risposta server inattesa: $response";
            fclose($socket);
            return false;
        }
        
        // EHLO
        fwrite($socket, "EHLO " . gethostname() . "\r\n");
        $response = $this->getResponse($socket);
        
        // STARTTLS se necessario
        if ($this->smtpEncryption === 'tls') {
            fwrite($socket, "STARTTLS\r\n");
            $response = $this->getResponse($socket);
            if (substr($response, 0, 3) !== '220') {
                $this->lastError = "STARTTLS fallito: $response";
                fclose($socket);
                return false;
            }
            
            // Abilita crittografia
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $this->lastError = "Impossibile abilitare TLS";
                fclose($socket);
                return false;
            }
            
            // EHLO di nuovo dopo TLS
            fwrite($socket, "EHLO " . gethostname() . "\r\n");
            $response = $this->getResponse($socket);
        }
        
        // AUTH LOGIN
        fwrite($socket, "AUTH LOGIN\r\n");
        $response = $this->getResponse($socket);
        if (substr($response, 0, 3) !== '334') {
            $this->lastError = "AUTH LOGIN fallito: $response";
            fclose($socket);
            return false;
        }
        
        // Username
        fwrite($socket, base64_encode($this->smtpUser) . "\r\n");
        $response = $this->getResponse($socket);
        if (substr($response, 0, 3) !== '334') {
            $this->lastError = "Username rifiutato: $response";
            fclose($socket);
            return false;
        }
        
        // Password
        fwrite($socket, base64_encode($this->smtpPassword) . "\r\n");
        $response = $this->getResponse($socket);
        if (substr($response, 0, 3) !== '235') {
            $this->lastError = "Autenticazione fallita: $response";
            fclose($socket);
            return false;
        }
        
        // MAIL FROM
        fwrite($socket, "MAIL FROM:<{$this->fromEmail}>\r\n");
        $response = $this->getResponse($socket);
        if (substr($response, 0, 3) !== '250') {
            $this->lastError = "MAIL FROM fallito: $response";
            fclose($socket);
            return false;
        }
        
        // RCPT TO
        fwrite($socket, "RCPT TO:<$to>\r\n");
        $response = $this->getResponse($socket);
        if (substr($response, 0, 3) !== '250') {
            $this->lastError = "RCPT TO fallito: $response";
            fclose($socket);
            return false;
        }
        
        // DATA
        fwrite($socket, "DATA\r\n");
        $response = $this->getResponse($socket);
        if (substr($response, 0, 3) !== '354') {
            $this->lastError = "DATA fallito: $response";
            fclose($socket);
            return false;
        }
        
        // Headers e body
        $contentType = $isHtml ? 'text/html' : 'text/plain';
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: $contentType; charset=UTF-8\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "\r\n";
        
        fwrite($socket, $headers . $body . "\r\n.\r\n");
        $response = $this->getResponse($socket);
        if (substr($response, 0, 3) !== '250') {
            $this->lastError = "Invio messaggio fallito: $response";
            fclose($socket);
            return false;
        }
        
        // QUIT
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        
        return true;
    }
    
    /**
     * Legge risposta dal socket SMTP
     */
    private function getResponse($socket): string {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return trim($response);
    }
    
    /**
     * Test connessione SMTP
     */
    public function testConnection(): array {
        $result = [
            'success' => false,
            'message' => '',
            'details' => []
        ];
        
        if (!$this->isConfigured()) {
            $result['message'] = 'Configurazione SMTP incompleta';
            return $result;
        }
        
        $result['details']['host'] = $this->smtpHost;
        $result['details']['port'] = $this->smtpPort;
        $result['details']['encryption'] = $this->smtpEncryption;
        $result['details']['user'] = $this->smtpUser;
        
        $port = $this->smtpPort;
        $host = $this->smtpHost;
        
        if ($this->smtpEncryption === 'ssl') {
            $host = 'ssl://' . $host;
        }
        
        $socket = @fsockopen($host, $port, $errno, $errstr, 10);
        if (!$socket) {
            $result['message'] = "Connessione fallita: $errstr ($errno)";
            return $result;
        }
        
        stream_set_timeout($socket, 10);
        $response = $this->getResponse($socket);
        
        if (substr($response, 0, 3) === '220') {
            $result['success'] = true;
            $result['message'] = 'Connessione SMTP riuscita';
            $result['details']['server_response'] = $response;
        } else {
            $result['message'] = "Risposta inattesa: $response";
        }
        
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        
        return $result;
    }
    
    /**
     * Invia email di test
     */
    public function sendTestEmail(string $to): bool {
        $subject = '🔔 MySanitario Sync - Test Email';
        $body = $this->buildTestEmailBody();
        return $this->send($to, $subject, $body, true);
    }
    
    /**
     * Costruisce body email di test
     */
    private function buildTestEmailBody(): string {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
        $html .= '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">';
        $html .= '<h2 style="color: #667eea;">✅ Test Email Riuscito</h2>';
        $html .= '<p>Questa è un\'email di test dal modulo <strong>MySanitario Sync</strong>.</p>';
        $html .= '<p>Se stai leggendo questo messaggio, la configurazione SMTP è corretta.</p>';
        $html .= '<hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">';
        $html .= '<p style="color: #888; font-size: 12px;">';
        $html .= 'Data/Ora: ' . date('d/m/Y H:i:s') . '<br>';
        $html .= 'Server: ' . gethostname() . '<br>';
        $html .= 'Client ID: ' . (defined('CLIENT_ID') ? CLIENT_ID : 'N/A');
        $html .= '</p>';
        $html .= '</div></body></html>';
        return $html;
    }
    
    /**
     * Invia report log via email
     */
    public function sendLogReport(string $to, array $logs, string $period = 'giornaliero'): bool {
        $subject = "📋 MySanitario Sync - Report $period";
        $body = $this->buildLogReportBody($logs, $period);
        return $this->send($to, $subject, $body, true);
    }
    
    /**
     * Costruisce body report log
     */
    private function buildLogReportBody(array $logs, string $period): string {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
        $html .= '<div style="font-family: Arial, sans-serif; max-width: 700px; margin: 0 auto; padding: 20px;">';
        
        // Header
        $html .= '<h2 style="color: #667eea; margin-bottom: 5px;">📋 Report ' . ucfirst($period) . '</h2>';
        $html .= '<p style="color: #888; margin-top: 0;">MySanitario Sync Module</p>';
        
        // Info sistema
        $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">';
        $html .= '<strong>🖥️ Sistema:</strong> ' . gethostname() . '<br>';
        $html .= '<strong>🆔 Client ID:</strong> ' . (defined('CLIENT_ID') ? CLIENT_ID : 'N/A') . '<br>';
        $html .= '<strong>📅 Generato:</strong> ' . date('d/m/Y H:i:s');
        $html .= '</div>';
        
        // Statistiche
        $stats = $this->calculateLogStats($logs);
        $html .= '<h3 style="color: #333;">📊 Riepilogo</h3>';
        $html .= '<table style="width: 100%; border-collapse: collapse;">';
        $html .= '<tr><td style="padding: 8px; border-bottom: 1px solid #eee;">Totale operazioni:</td><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>' . $stats['total'] . '</strong></td></tr>';
        $html .= '<tr><td style="padding: 8px; border-bottom: 1px solid #eee;">✅ Successi:</td><td style="padding: 8px; border-bottom: 1px solid #eee; color: green;"><strong>' . $stats['success'] . '</strong></td></tr>';
        $html .= '<tr><td style="padding: 8px; border-bottom: 1px solid #eee;">⚠️ Warning:</td><td style="padding: 8px; border-bottom: 1px solid #eee; color: orange;"><strong>' . $stats['warning'] . '</strong></td></tr>';
        $html .= '<tr><td style="padding: 8px; border-bottom: 1px solid #eee;">❌ Errori:</td><td style="padding: 8px; border-bottom: 1px solid #eee; color: red;"><strong>' . $stats['error'] . '</strong></td></tr>';
        $html .= '</table>';
        
        // Ultimi log
        $html .= '<h3 style="color: #333; margin-top: 30px;">📜 Ultimi Log</h3>';
        $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 12px; max-height: 400px; overflow-y: auto;">';
        
        if (empty($logs)) {
            $html .= '<p style="color: #888;">Nessun log disponibile</p>';
        } else {
            foreach (array_slice($logs, -50) as $log) {
                $color = '#333';
                $icon = 'ℹ️';
                if (strpos($log, '[ERROR]') !== false) {
                    $color = '#dc3545';
                    $icon = '❌';
                } elseif (strpos($log, '[WARNING]') !== false) {
                    $color = '#ffc107';
                    $icon = '⚠️';
                } elseif (strpos($log, '[INFO]') !== false) {
                    $color = '#28a745';
                    $icon = '✅';
                }
                $html .= '<div style="color: ' . $color . '; margin-bottom: 5px; word-wrap: break-word;">' . $icon . ' ' . htmlspecialchars($log) . '</div>';
            }
        }
        $html .= '</div>';
        
        // Footer
        $html .= '<hr style="border: none; border-top: 1px solid #eee; margin: 30px 0 15px 0;">';
        $html .= '<p style="color: #888; font-size: 11px; text-align: center;">';
        $html .= 'Questo messaggio è stato generato automaticamente da MySanitario Sync Module.<br>';
        $html .= 'Per modificare le impostazioni, accedi al pannello di controllo.';
        $html .= '</p>';
        
        $html .= '</div></body></html>';
        return $html;
    }
    
    /**
     * Calcola statistiche dai log
     */
    private function calculateLogStats(array $logs): array {
        $stats = ['total' => count($logs), 'success' => 0, 'warning' => 0, 'error' => 0, 'info' => 0];
        
        foreach ($logs as $log) {
            if (strpos($log, '[ERROR]') !== false) {
                $stats['error']++;
            } elseif (strpos($log, '[WARNING]') !== false) {
                $stats['warning']++;
            } elseif (strpos($log, '[INFO]') !== false) {
                $stats['success']++;
            } else {
                $stats['info']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Ottiene ultimo errore
     */
    public function getLastError(): ?string {
        return $this->lastError;
    }
}
