<?php
declare(strict_types=1);

/**
 * Oblio API Integration Class
 * Based on official documentation: https://www.oblio.eu/api
 * Updated: October 2025
 */

class OblioAPI {
    private PDO $pdo;
    private ?string $accessToken = null;
    private array $settings = [];
    private string $baseUrl = 'https://www.oblio.eu';
    private int $tokenExpiry = 0;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->initTable();
        $this->loadSettings();
    }
    
    /**
     * Initialize database tables
     */
    private function initTable(): void {
        // Oblio settings table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS oblio_settings (
                id INT PRIMARY KEY DEFAULT 1,
                email VARCHAR(255) NOT NULL,
                api_key VARCHAR(255) NOT NULL,
                company VARCHAR(255) NOT NULL,
                cif VARCHAR(32) DEFAULT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        
        // Oblio invoices table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS oblio_invoices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                company_cui VARCHAR(32) NOT NULL,
                oblio_id VARCHAR(100) DEFAULT NULL,
                type ENUM('invoice', 'proforma', 'notice') DEFAULT 'invoice',
                series VARCHAR(20) NOT NULL,
                number INT NOT NULL,
                date DATE NOT NULL,
                due_date DATE DEFAULT NULL,
                client_name VARCHAR(255),
                client_cif VARCHAR(32),
                subtotal DECIMAL(10,2) DEFAULT 0,
                vat DECIMAL(10,2) DEFAULT 0,
                total DECIMAL(10,2) DEFAULT 0,
                status ENUM('draft', 'sent', 'paid', 'cancelled') DEFAULT 'draft',
                items JSON,
                oblio_data JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_company (company_cui),
                INDEX idx_user (user_id),
                UNIQUE KEY unique_invoice (series, number, type, company_cui)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }
    
    /**
     * Load settings from database
     */
    private function loadSettings(): void {
		global $_SESSION;
        try {
			$user_id = $_SESSION['user_id'];
            $stmt = $this->pdo->query("SELECT * FROM oblio_settings WHERE id=".$user_id." LIMIT 1");
            $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            $this->settings = $row ?: [];
        } catch (Exception $e) {
            error_log("Oblio: Failed to load settings - " . $e->getMessage());
            $this->settings = [];
        }
    }
    
    /**
     * Check if Oblio is configured
     */
    public function isConfigured(): bool {
        return !empty($this->settings['email']) && 
               !empty($this->settings['api_key']) && 
               !empty($this->settings['cif']);
    }
    
    /**
     * Get current settings
     */
    public function getSettings(): array {
        return [
            'email' => $this->settings['email'] ?? '',
            'company' => $this->settings['company'] ?? '',
            'cif' => $this->settings['cif'] ?? '',
            'configured' => $this->isConfigured()
        ];
    }
    
    /**
     * Save Oblio settings
     */
    public function saveSettings(string $email, string $secret, string $company, string $cif): void {
        // Remove RO prefix from CIF if present
        $cif = preg_replace('/^RO/i', '', $cif);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO oblio_settings (id, email, api_key, company, cif) 
            VALUES (1, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                email = VALUES(email),
                api_key = VALUES(api_key),
                company = VALUES(company),
                cif = VALUES(cif)
        ");
        
        $stmt->execute([$email, $secret, $company, $cif]);
        
        // Reload settings
        $this->loadSettings();
        
        // Clear token to force re-authentication
        $this->accessToken = null;
        $this->tokenExpiry = 0;
    }
    
    /**
     * Get OAuth2 access token
     * As per API docs: POST to /api/authorize/token
     */
    private function getAccessToken(): string {
        // Return cached token if still valid
        if ($this->accessToken && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }
        
        if (!$this->isConfigured()) {
            throw new Exception('Oblio not configured');
        }
        
        $ch = curl_init($this->baseUrl . '/api/authorize/token');
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'client_id' => $this->settings['email'],
                'client_secret' => $this->settings['api_key'],
                'grant_type' => 'client_credentials'
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get access token: HTTP ' . $httpCode);
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            throw new Exception('Invalid token response');
        }
        
        $this->accessToken = $data['access_token'];
        // Token expires in 3600 seconds, refresh 5 minutes before
        $this->tokenExpiry = time() + ($data['expires_in'] ?? 3600) - 300;
        
        return $this->accessToken;
    }
    
    /**
     * Make API request
     */
    private function request(string $endpoint, string $method = 'GET', ?array $data = null): array {
        $token = $this->getAccessToken();
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $error = $result['statusMessage'] ?? 'API Error';
            throw new Exception($error . ' (HTTP ' . $httpCode . ')');
        }
        
        return $result;
    }
    
    // ==================== NOMENCLATURE METHODS ====================
    
    /**
     * Get list of companies associated with account
     */
    public function getCompanies(): array {
        $result = $this->request('/api/nomenclature/companies');
        return $result['data'] ?? [];
    }
    
    /**
     * Get VAT rates for a company
     */
    public function getVatRates(?string $cif = null): array {
        $cif = $cif ?? $this->settings['cif'];
        $result = $this->request('/api/nomenclature/vat_rates?cif=RO' . $cif);
        return $result['data'] ?? [];
    }
    
    /**
     * Get clients for a company
     */
    public function getClients(?string $cif = null, ?string $name = null, ?string $clientCif = null, int $offset = 0): array {
        $cif = $cif ?? $this->settings['cif'];
        $params = ['cif' => 'RO' . $cif, 'offset' => $offset];
        
        if ($name) $params['name'] = $name;
        if ($clientCif) $params['clientCif'] = $clientCif;
        
        $query = http_build_query($params);
        $result = $this->request('/api/nomenclature/clients?' . $query);
        return $result['data'] ?? [];
    }
    
    /**
     * Get products for a company
     */
    public function getProducts(?string $cif = null, int $offset = 0): array {
        $cif = $cif ?? $this->settings['cif'];
        $query = http_build_query(['cif' => 'RO' . $cif, 'offset' => $offset]);
        $result = $this->request('/api/nomenclature/products?' . $query);
        return $result['data'] ?? [];
    }
    
    /**
     * Get document series for a company
     */
    public function getSeries(?string $cif = null): array {
        $cif = $cif ?? $this->settings['cif'];
        $result = $this->request('/api/nomenclature/series?cif=RO' . $cif);
        return $result['data'] ?? [];
    }
    
    /**
     * Get languages available for a company
     */
    public function getLanguages(?string $cif = null): array {
        $cif = $cif ?? $this->settings['cif'];
        $result = $this->request('/api/nomenclature/languages?cif=RO' . $cif);
        return $result['data'] ?? [];
    }
    
    /**
     * Get managements (stock locations) for a company
     */
    public function getManagements(?string $cif = null): array {
        $cif = $cif ?? $this->settings['cif'];
        $result = $this->request('/api/nomenclature/management?cif=RO' . $cif);
        return $result['data'] ?? [];
    }
    
    // ==================== DOCUMENT METHODS ====================
    
    /**
     * Create invoice
     */
    public function createInvoice(array $data): array {
        $result = $this->request('/api/docs/invoice', 'POST', $data);
        return $result['data'] ?? [];
    }
    
    /**
     * Create proforma invoice
     */
    public function createProforma(array $data): array {
        $result = $this->request('/api/docs/proforma', 'POST', $data);
        return $result['data'] ?? [];
    }
    
    /**
     * Create notice (aviz)
     */
    public function createNotice(array $data): array {
        $result = $this->request('/api/docs/notice', 'POST', $data);
        return $result['data'] ?? [];
    }
    
    /**
     * Get invoice details
     */
    public function getInvoice(string $seriesName, int $number, ?string $cif = null): array {
        $cif = $cif ?? $this->settings['cif'];
        $query = http_build_query(['cif' => 'RO' . $cif, 'seriesName' => $seriesName, 'number' => $number]);
        $result = $this->request('/api/docs/invoice?' . $query);
        return $result['data'] ?? [];
    }
    
    /**
     * Get proforma details
     */
    public function getProforma(string $seriesName, int $number, ?string $cif = null): array {
        $cif = $cif ?? $this->settings['cif'];
        $query = http_build_query(['cif' => 'RO' . $cif, 'seriesName' => $seriesName, 'number' => $number]);
        $result = $this->request('/api/docs/proforma?' . $query);
        return $result['data'] ?? [];
    }
    
    /**
     * Cancel invoice
     */
    public function cancelInvoice(string $seriesName, int $number, ?string $cif = null): array {
        $cif = $cif ?? $this->settings['cif'];
        $data = ['cif' => 'RO' . $cif, 'seriesName' => $seriesName, 'number' => $number];
        $result = $this->request('/api/docs/invoice/cancel', 'PUT', $data);
        return $result['data'] ?? [];
    }
    
    /**
     * Restore cancelled invoice
     */
    public function restoreInvoice(string $seriesName, int $number, ?string $cif = null): array {
        $cif = $cif ?? $this->settings['cif'];
        $data = ['cif' => 'RO' . $cif, 'seriesName' => $seriesName, 'number' => $number];
        $result = $this->request('/api/docs/invoice/restore', 'PUT', $data);
        return $result['data'] ?? [];
    }
    
    /**
     * Delete invoice (only last in series)
     */
    public function deleteInvoice(string $seriesName, int $number, ?string $cif = null): array {
        $cif = $cif ?? $this->settings['cif'];
        $query = http_build_query(['cif' => 'RO' . $cif, 'seriesName' => $seriesName, 'number' => $number]);
        $result = $this->request('/api/docs/invoice?' . $query, 'DELETE');
        return $result['data'] ?? [];
    }
    
    /**
     * List invoices with filters
     */
    public function listInvoices(array $filters = []): array {
        $cif = $filters['cif'] ?? $this->settings['cif'];
        $filters['cif'] = 'RO' . $cif;
        
        $query = http_build_query($filters);
        $result = $this->request('/api/docs/invoice/list?' . $query);
        return $result['data'] ?? [];
    }
    
    /**
     * Collect invoice payment
     */
    public function collectInvoice(string $seriesName, int $number, array $collectData, ?string $cif = null): array {
        $cif = $cif ?? $this->settings['cif'];
        $data = [
            'cif' => 'RO' . $cif,
            'seriesName' => $seriesName,
            'number' => $number,
            'collect' => $collectData
        ];
        $result = $this->request('/api/docs/invoice/collect', 'PUT', $data);
        return $result['data'] ?? [];
    }
    
    /**
     * Send e-Invoice to SPV (ANAF)
     */
    public function sendEInvoice(string $seriesName, int $number, ?string $cif = null): array {
        $cif = $cif ?? $this->settings['cif'];
        $data = ['cif' => 'RO' . $cif, 'seriesName' => $seriesName, 'number' => $number];
        $result = $this->request('/api/docs/einvoice', 'POST', $data);
        return $result['data'] ?? [];
    }
    
    /**
     * Download e-Invoice archive
     */
    public function downloadEInvoiceArchive(string $seriesName, int $number, ?string $cif = null): string {
        $cif = $cif ?? $this->settings['cif'];
        $query = http_build_query(['cif' => 'RO' . $cif, 'seriesName' => $seriesName, 'number' => $number]);
        
        // This returns the actual file, not JSON
        $token = $this->getAccessToken();
        return $this->baseUrl . '/api/docs/einvoice?' . $query . '&access_token=' . $token;
    }
    
    // ==================== SYNC METHODS ====================
    
    /**
     * Sync clients from Oblio to local database
     */
    public function syncClientsFromOblio(): int {
        if (!$this->isConfigured()) {
            throw new Exception('Oblio not configured');
        }
        
        $synced = 0;
        $offset = 0;
        $limit = 250; // API returns max 250 results
        
        do {
            $clients = $this->getClients(null, null, null, $offset);
            
            foreach ($clients as $client) {
                try {
                    // Skip invalid clients
                    if (empty($client['cif']) || $client['cif'] === '-') {
                        continue;
                    }
                    
                    // Clean CIF - remove RO prefix and convert to integer
                    $cif = preg_replace('/^RO/i', '', $client['cif']);
                    $cif = preg_replace('/[^0-9]/', '', $cif); // Remove non-numeric characters
                    
                    if (empty($cif)) {
                        continue;
                    }
                    
                    $stmt = $this->pdo->prepare("
                        INSERT INTO companies (CUI, Name, Reg, Adress)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            Name = VALUES(Name),
                            Reg = VALUES(Reg),
                            Adress = VALUES(Adress)
                    ");
                    
                    $address = trim(
                        ($client['address'] ?? '') . ' ' . 
                        ($client['city'] ?? '') . ' ' . 
                        ($client['state'] ?? '')
                    );
                    
                    $stmt->execute([
                        (int)$cif,
                        $client['name'] ?? '',
                        $client['rc'] ?? '-',
                        $address ?: '-'
                    ]);
                    
                    $synced++;
                } catch (Exception $e) {
                    error_log("Failed to sync client {$client['cif']}: " . $e->getMessage());
                }
            }
            
            $offset += $limit;
        } while (count($clients) === $limit);
        
        return $synced;
    }
    
    /**
     * Sync invoices from Oblio to local database
     */
    public function syncInvoicesFromOblio(int $year, ?int $month = null): int {
        if (!$this->isConfigured()) {
            throw new Exception('Oblio not configured');
        }
        
        $synced = 0;
        $offset = 0;
        $limit = 100;
        
        // Build date filters
        $filters = [
            'limitPerPage' => $limit,
            'offset' => $offset,
            'withProducts' => 1
        ];
        
        if ($year && $month) {
            $filters['issuedAfter'] = sprintf('%04d-%02d-01', $year, $month);
            $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $filters['issuedBefore'] = sprintf('%04d-%02d-%02d', $year, $month, $lastDay);
        } elseif ($year) {
            $filters['issuedAfter'] = "$year-01-01";
            $filters['issuedBefore'] = "$year-12-31";
        }
        
        do {
            $filters['offset'] = $offset;
            $invoices = $this->listInvoices($filters);
            
            foreach ($invoices as $invoice) {
                try {
                    $this->saveLocalInvoice($invoice);
                    $synced++;
                } catch (Exception $e) {
                    error_log("Failed to sync invoice: " . $e->getMessage());
                }
            }
            
            $offset += $limit;
        } while (count($invoices) === $limit);
        
        return $synced;
    }
    
    /**
     * Save invoice to local database
     */
    private function saveLocalInvoice(array $invoice): void {
        $client = $invoice['client'] ?? [];
        
        $stmt = $this->pdo->prepare("
            INSERT INTO oblio_invoices 
            (user_id, company_cui, oblio_id, type, series, number, date, due_date, 
             client_name, client_cif, subtotal, vat, total, status, oblio_data)
            VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                oblio_id = VALUES(oblio_id),
                date = VALUES(date),
                due_date = VALUES(due_date),
                client_name = VALUES(client_name),
                client_cif = VALUES(client_cif),
                total = VALUES(total),
                status = VALUES(status),
                oblio_data = VALUES(oblio_data),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $type = strtolower($invoice['type'] ?? 'invoice');
        $status = ($invoice['canceled'] ?? 0) ? 'cancelled' : (($invoice['draft'] ?? 0) ? 'draft' : 'sent');
        
        $stmt->execute([
            $this->settings['cif'],
            $invoice['id'] ?? null,
            $type === 'factura' ? 'invoice' : ($type === 'proforma' ? 'proforma' : 'notice'),
            $invoice['seriesName'] ?? '',
            $invoice['number'] ?? 0,
            $invoice['issueDate'] ?? date('Y-m-d'),
            $invoice['dueDate'] ?? null,
            $client['name'] ?? '',
            $client['cif'] ?? '',
            $invoice['total'] ?? 0,
            $status,
            json_encode($invoice)
        ]);
    }
}