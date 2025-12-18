<?php
/**
 * Classe Database - Gestione connessione MySQL
 */

namespace MySanitario\Sync;

class Database {
    private static ?Database $instance = null;
    private ?\mysqli $connection = null;
    private array $config;
    private string $lastError = '';
    
    private function __construct(array $config) {
        $this->config = $config;
    }
    
    /**
     * Ottiene l'istanza singleton del database
     */
    public static function getInstance(?array $config = null): Database {
        if (self::$instance === null) {
            if ($config === null) {
                $config = getDbConfig();
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    /**
     * Connette al database
     */
    public function connect(): bool {
        if ($this->connection !== null) {
            return true;
        }
        
        try {
            $this->connection = new \mysqli(
                $this->config['host'],
                $this->config['user'],
                $this->config['password'],
                $this->config['database']
            );
            
            if ($this->connection->connect_error) {
                $this->lastError = $this->connection->connect_error;
                $this->connection = null;
                return false;
            }
            
            $this->connection->set_charset($this->config['charset'] ?? 'utf8mb4');
            return true;
            
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Esegue una query SELECT e restituisce i risultati
     */
    public function query(string $sql): ?array {
        if (!$this->connect()) {
            return null;
        }
        
        $result = $this->connection->query($sql);
        
        if ($result === false) {
            $this->lastError = $this->connection->error;
            return null;
        }
        
        if ($result === true) {
            return [];
        }
        
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
        
        return $rows;
    }
    
    /**
     * Esegue una query di modifica (INSERT, UPDATE, DELETE)
     */
    public function execute(string $sql): bool {
        if (!$this->connect()) {
            return false;
        }
        
        $result = $this->connection->query($sql);
        
        if ($result === false) {
            $this->lastError = $this->connection->error;
            return false;
        }
        
        return true;
    }
    
    /**
     * Esegue multiple query in una transazione
     */
    public function executeTransaction(array $queries): bool {
        if (!$this->connect()) {
            return false;
        }
        
        $this->connection->begin_transaction();
        
        try {
            foreach ($queries as $sql) {
                if (!$this->connection->query($sql)) {
                    throw new \Exception($this->connection->error);
                }
            }
            $this->connection->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->connection->rollback();
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Escape stringa per query
     */
    public function escape(string $value): string {
        if (!$this->connect()) {
            return addslashes($value);
        }
        return $this->connection->real_escape_string($value);
    }
    
    /**
     * Restituisce l'ultimo ID inserito
     */
    public function lastInsertId(): int {
        return $this->connection ? $this->connection->insert_id : 0;
    }
    
    /**
     * Restituisce il numero di righe affette dall'ultima query
     */
    public function affectedRows(): int {
        return $this->connection ? $this->connection->affected_rows : 0;
    }
    
    /**
     * Restituisce l'ultimo errore
     */
    public function getLastError(): string {
        return $this->lastError;
    }
    
    /**
     * Testa la connessione
     */
    public function testConnection(): array {
        $connected = $this->connect();
        return [
            'success' => $connected,
            'message' => $connected ? 'Connessione riuscita' : $this->lastError,
            'server_info' => $connected ? $this->connection->server_info : null
        ];
    }
    
    /**
     * Chiude la connessione
     */
    public function close(): void {
        if ($this->connection !== null) {
            $this->connection->close();
            $this->connection = null;
        }
    }
    
    /**
     * Reset istanza singleton (utile per cambiare configurazione)
     */
    public static function resetInstance(): void {
        if (self::$instance !== null) {
            self::$instance->close();
            self::$instance = null;
        }
    }
}
