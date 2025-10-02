<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../oblio_api.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

try {
    $api = new OblioAPI($pdo);
    
    if (!$api->isConfigured()) {
        throw new Exception('Oblio not configured. Please set up credentials first.');
    }
    
    // Fetch current month/year or use parameters
    $year = isset($_POST['year']) ? (int)$_POST['year'] : (int)date('Y');
    $month = isset($_POST['month']) ? (int)$_POST['month'] : (int)date('m');
    
    $invoices = $api->fetchInvoices($year, $month);
    $proformas = $api->fetchProformas($year, $month);
    
    $imported = 0;
    $errors = [];
    
    foreach (array_merge($invoices, $proformas) as $inv) {
        try {
            // Validate required fields
            if (empty($inv['seriesName']) || empty($inv['number'])) {
                $errors[] = "Invalid invoice data - missing series or number";
                continue;
            }
            
            // Determine type
            $type = isset($inv['type']) && $inv['type'] === 'proforma' ? 'proforma' : 'invoice';
            
            // Find or create company by CIF
            $clientCif = $inv['client']['cif'] ?? null;
            $companyCui = null;
            
            if ($clientCif) {
                $stmt = $pdo->prepare("SELECT CUI FROM companies WHERE CUI = ? LIMIT 1");
                $stmt->execute([$clientCif]);
                $company = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$company) {
                    // Create company if doesn't exist
                    $stmt = $pdo->prepare("
                        INSERT INTO companies (CUI, Name, Reg, Adress) 
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE Name = VALUES(Name)
                    ");
                    $stmt->execute([
                        $clientCif,
                        $inv['client']['name'] ?? 'Client Import',
                        $inv['client']['rc'] ?? '',
                        $inv['client']['address'] ?? ''
                    ]);
                }
                $companyCui = (int)$clientCif;
            }
            
            // Parse items if available
            $items = [];
            if (isset($inv['products']) && is_array($inv['products'])) {
                foreach ($inv['products'] as $product) {
                    $items[] = [
                        'description' => $product['name'] ?? '',
                        'quantity' => $product['quantity'] ?? 1,
                        'price' => $product['price'] ?? 0,
                        'vat' => $product['vatPercentage'] ?? 19
                    ];
                }
            }
            
            // Calculate totals
            $subtotal = 0;
            $vat = 0;
            foreach ($items as $item) {
                $itemTotal = $item['quantity'] * $item['price'];
                $subtotal += $itemTotal;
                $vat += $itemTotal * ($item['vat'] / 100);
            }
            $total = $subtotal + $vat;
            
            // If no items, use total from invoice
            if (empty($items)) {
                $total = (float)($inv['total'] ?? 0);
                $subtotal = $total / 1.19; // Approximate without VAT
                $vat = $total - $subtotal;
            }
            
            // Map Oblio status to our status
            $status = 'sent';
            if (isset($inv['status'])) {
                switch ($inv['status']) {
                    case 'cancelled': $status = 'cancelled'; break;
                    case 'paid': $status = 'paid'; break;
                    default: $status = 'sent';
                }
            }
            
            // Insert or update invoice
            $stmt = $pdo->prepare("
                INSERT INTO oblio_invoices 
                (user_id, company_cui, oblio_id, type, series, number, date, due_date,
                 client_name, client_cif, subtotal, vat, total, status, items, oblio_data)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    oblio_id = VALUES(oblio_id),
                    client_name = VALUES(client_name),
                    total = VALUES(total),
                    status = VALUES(status),
                    oblio_data = VALUES(oblio_data),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                $userId,
                $companyCui,
                $inv['seriesName'] . $inv['number'],
                $type,
                $inv['seriesName'],
                $inv['number'],
                $inv['issueDate'] ?? date('Y-m-d'),
                $inv['dueDate'] ?? null,
                $inv['client']['name'] ?? 'Unknown',
                $clientCif,
                $subtotal,
                $vat,
                $total,
                $status,
                json_encode($items),
                json_encode($inv)
            ]);
            
            $imported++;
            
        } catch (Exception $e) {
            $errors[] = "Error importing invoice {$inv['seriesName']}{$inv['number']}: " . $e->getMessage();
            error_log("Invoice import error: " . $e->getMessage());
        }
    }
    
    $response = [
        'success' => true,
        'data' => [
            'imported' => $imported,
            'total' => count($invoices) + count($proformas),
            'invoices' => count($invoices),
            'proformas' => count($proformas)
        ]
    ];
    
    if (!empty($errors)) {
        $response['warnings'] = $errors;
    }
    
    echo json_encode($response);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>