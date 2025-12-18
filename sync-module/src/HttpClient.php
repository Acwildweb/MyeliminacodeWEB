<?php
/**
 * HttpClient - Client HTTP per chiamate API
 */

namespace MySanitario\Sync;

class HttpClient {
    private int $timeout = 30;
    private string $lastError = '';
    private int $lastHttpCode = 0;
    
    public function __construct(int $timeout = 30) {
        $this->timeout = $timeout;
    }
    
    /**
     * Esegue una richiesta GET
     */
    public function get(string $url, array $params = []): ?string {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $this->request('GET', $url);
    }
    
    /**
     * Esegue una richiesta POST
     */
    public function post(string $url, array $data = []): ?string {
        return $this->request('POST', $url, $data);
    }
    
    /**
     * Esegue la richiesta HTTP
     */
    private function request(string $method, string $url, array $data = []): ?string {
        $ch = curl_init();
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,  // Per certificati self-signed
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'MySanitario-Sync/' . (defined('SYNC_VERSION') ? SYNC_VERSION : '1.0'),
        ];
        
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($data);
        }
        
        curl_setopt_array($ch, $options);
        
        $response = curl_exec($ch);
        $this->lastHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            $this->lastError = curl_error($ch);
            curl_close($ch);
            return null;
        }
        
        curl_close($ch);
        return $response;
    }
    
    /**
     * Scarica un file
     */
    public function downloadFile(string $url, string $savePath): bool {
        // Crea directory se non esiste
        $dir = dirname($savePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $ch = curl_init();
        
        $fp = fopen($savePath, 'w');
        if ($fp === false) {
            $this->lastError = "Impossibile creare il file: $savePath";
            return false;
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_FILE => $fp,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);
        
        $success = curl_exec($ch);
        $this->lastHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (!$success) {
            $this->lastError = curl_error($ch);
        }
        
        curl_close($ch);
        fclose($fp);
        
        // Elimina file se download fallito
        if (!$success || $this->lastHttpCode >= 400) {
            unlink($savePath);
            return false;
        }
        
        return true;
    }
    
    /**
     * Decodifica risposta JSON
     */
    public function getJson(string $url, array $params = []): ?array {
        $response = $this->get($url, $params);
        
        if ($response === null) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = 'Errore decodifica JSON: ' . json_last_error_msg();
            return null;
        }
        
        return $data;
    }
    
    public function getLastError(): string {
        return $this->lastError;
    }
    
    public function getLastHttpCode(): int {
        return $this->lastHttpCode;
    }
}
