<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/oblio_api.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$f = $_GET['f'] ?? '';

try {
    $oblio = new OblioAPI($pdo);
    
    if ($f === 'get_oblio_vat_rates') {
        $rates = $oblio->getVatRates();
        echo json_encode(['success' => true, 'data' => $rates]);
        exit;
    }
    
    if ($f === 'get_oblio_series') {
        $series = $oblio->getSeries();
        echo json_encode(['success' => true, 'data' => $series]);
        exit;
    }
    
    if ($f === 'get_company_invoices') {
        $companyCui = $_POST['company_cui'] ?? '';
        if (!$companyCui) {
            throw new Exception('CUI companie lipseste');
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM oblio_invoices 
            WHERE client_cif = ? AND user_id = ?
            ORDER BY date DESC, created_at DESC
        ");
        $stmt->execute([$companyCui, $userId]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $invoices]);
        exit;
    }
    
    if ($f === 'create_oblio_invoice' || $f === 'create_oblio_proforma') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['products']) || empty($data['products'])) {
            throw new Exception('Date invalide sau produse lipsesc');
        }
        
        $type = $f === 'create_oblio_proforma' ? 'proforma' : 'invoice';
        
        $settings = $oblio->getSettings();
        if (!isset($data['cif'])) {
            $data['cif'] = 'RO' . $settings['cif'];
        }
        
        // FIX #1: Add required product type for Oblio API
        foreach ($data['products'] as &$product) {
            if (!isset($product['productType'])) {
                $product['productType'] = 'Serviciu'; // Default to Service
            }
            if (!isset($product['code'])) {
                $product['code'] = ''; // Empty code is allowed
            }
            if (!isset($product['description'])) {
                $product['description'] = '';
            }
        }
        unset($product);
        
        $result = $type === 'proforma' ? $oblio->createProforma($data) : $oblio->createInvoice($data);
        
        if (!$result || !isset($result['seriesName'], $result['number'])) {
            throw new Exception('Raspuns invalid de la Oblio API');
        }
        
        $clientCif = isset($data['client']['cif']) ? str_replace('RO', '', $data['client']['cif']) : '';
        $clientName = $data['client']['name'] ?? '';
        
        $subtotal = 0;
        $totalVat = 0;
        foreach ($data['products'] as $item) {
            $itemSubtotal = $item['quantity'] * $item['price'];
            $itemVat = $itemSubtotal * ($item['vatPercentage'] / 100);
            $subtotal += $itemSubtotal;
            $totalVat += $itemVat;
        }
        $total = $subtotal + $totalVat;
        
        $sourceType = $data['sourceType'] ?? 'manual';
        $sourceId = $data['sourceId'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO oblio_invoices (
                user_id, company_cui, source_type, source_id, oblio_id, type, series, number, 
                date, due_date, client_name, client_cif, subtotal, vat, total, status, items, oblio_data
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'sent', ?, ?)
            ON DUPLICATE KEY UPDATE 
                oblio_id = VALUES(oblio_id),
                client_name = VALUES(client_name),
                client_cif = VALUES(client_cif),
                subtotal = VALUES(subtotal),
                vat = VALUES(vat),
                total = VALUES(total),
                status = VALUES(status),
                items = VALUES(items),
                oblio_data = VALUES(oblio_data),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            $userId,
            $settings['cif'],
            $sourceType,
            $sourceId,
            $result['id'] ?? null,
            $type,
            $result['seriesName'],
            $result['number'],
            $data['issueDate'],
            $data['dueDate'] ?? null,
            $clientName,
            $clientCif,
            $subtotal,
            $totalVat,
            $total,
            json_encode($data['products'], JSON_UNESCAPED_UNICODE),
            json_encode($result, JSON_UNESCAPED_UNICODE)
        ]);
        
        echo json_encode([
            'success' => true,
            'data' => $result,
            'message' => ($type === 'proforma' ? 'Proformă' : 'Factură') . ' creată cu succes'
        ]);
        exit;
    }
    
    if ($f === 'get_invoice_details') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            throw new Exception('ID lipseste');
        }
        
        $stmt = $pdo->prepare("SELECT * FROM oblio_invoices WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            throw new Exception('Factura nu a fost gasita');
        }
        
        // FIX #2: Decode oblio_data if it's a string
        if (isset($invoice['oblio_data']) && is_string($invoice['oblio_data'])) {
            $invoice['oblio_data_decoded'] = json_decode($invoice['oblio_data'], true);
        }
        
        echo json_encode(['success' => true, 'data' => $invoice]);
        exit;
    }
    
    if ($f === 'cancel_oblio_invoice') {
        $series = $_POST['series'] ?? '';
        $number = (int)($_POST['number'] ?? 0);
        
        if (!$series || !$number) {
            throw new Exception('Serie si numar sunt obligatorii');
        }
        
        $result = $oblio->cancelInvoice($series, $number);
        
        $stmt = $pdo->prepare("
            UPDATE oblio_invoices 
            SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP 
            WHERE series = ? AND number = ? AND user_id = ?
        ");
        $stmt->execute([$series, $number, $userId]);
        
        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }
    
    if ($f === 'sync_invoices_from_oblio') {
        $year = isset($_POST['year']) ? (int)$_POST['year'] : (int)date('Y');
        
        $settings = $oblio->getSettings();
        $filters = [];
        $filters['issuedAfter'] = "$year-01-01";
        $filters['issuedBefore'] = "$year-12-31";
        
        $invoices = $oblio->listInvoices($filters);
        
        $synced = 0;
        foreach ($invoices as $inv) {
            $clientCif = isset($inv['client']['cif']) ? str_replace('RO', '', $inv['client']['cif']) : '';
            
            $stmt = $pdo->prepare("
                INSERT INTO oblio_invoices (
                    user_id, company_cui, oblio_id, type, series, number, date, due_date,
                    client_name, client_cif, subtotal, vat, total, status, items, oblio_data
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    oblio_id = VALUES(oblio_id),
                    status = VALUES(status),
                    oblio_data = VALUES(oblio_data),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $type = 'invoice';
            $status = ($inv['cancelled'] ?? false) ? 'cancelled' : 'sent';
            
            $stmt->execute([
                $userId,
                $settings['cif'],
                $inv['id'] ?? null,
                $type,
                $inv['seriesName'],
                $inv['number'],
                $inv['issueDate'],
                $inv['dueDate'] ?? null,
                $inv['client']['name'] ?? '',
                $clientCif,
                $inv['subtotal'] ?? 0,
                $inv['vat'] ?? 0,
                $inv['total'] ?? 0,
                $status,
                json_encode($inv['products'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($inv, JSON_UNESCAPED_UNICODE)
            ]);
            
            $synced++;
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Sincronizate $synced facturi",
            'synced' => $synced
        ]);
        exit;
    }
if ($f === 'download_invoice_pdf') {
    try {
        $id = (int)($_GET['id'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT * FROM oblio_invoices WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            http_response_code(404);
            echo "Factură negăsită";
            exit;
        }
        
        // Try to get PDF link from Oblio
        if ($oblio->isConfigured() && $invoice['oblio_id']) {
            try {
                $oblioInvoice = $oblio->getInvoice($invoice['series'], (int)$invoice['number']);
                if (isset($oblioInvoice['link'])) {
                    header('Location: ' . $oblioInvoice['link']);
                    exit;
                }
            } catch (Exception $e) {
                error_log("Failed to get Oblio PDF: " . $e->getMessage());
            }
        }
        
        // Fallback to local print
        header('Location: print_invoice.php?id=' . $id);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo "Eroare: " . $e->getMessage();
        exit;
    }
}    
    if ($f === 'import_from_offer') {
        $offerId = (int)($_POST['offer_id'] ?? 0);
        if (!$offerId) {
            throw new Exception('ID oferta lipseste');
        }
        
        $stmt = $pdo->prepare("
            SELECT o.*, oi.description, oi.quantity, oi.unit_price 
            FROM offers o
            LEFT JOIN offer_items oi ON o.id = oi.offer_id
            WHERE o.id = ? AND o.user_id = ?
        ");
        $stmt->execute([$offerId, $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!$rows) {
            throw new Exception('Oferta nu a fost gasita');
        }
        
        $offer = $rows[0];
        $items = [];
        
        foreach ($rows as $row) {
            if ($row['description']) {
                $items[] = [
                    'description' => $row['description'],
                    'quantity' => (float)$row['quantity'],
                    'price' => (float)$row['unit_price']
                ];
            }
        }
        
        $stmt = $pdo->prepare("SELECT Name, Adress FROM companies WHERE CUI = ?");
        $stmt->execute([$offer['company_cui']]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'client_cif' => $offer['company_cui'],
                'client_name' => $company['Name'] ?? '',
                'client_address' => $company['Adress'] ?? '',
                'items' => $items,
                'sourceType' => 'offer',
                'sourceId' => $offerId
            ]
        ]);
        exit;
    }
    
    if ($f === 'import_from_contract') {
        $contractId = (int)($_POST['contract_id'] ?? 0);
        if (!$contractId) {
            throw new Exception('ID contract lipseste');
        }
        
        $stmt = $pdo->prepare("
            SELECT c.*, o.id as offer_id
            FROM contracts c
            LEFT JOIN offers o ON c.offer_id = o.id
            WHERE c.id = ? AND c.user_id = ?
        ");
        $stmt->execute([$contractId, $userId]);
        $contract = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$contract) {
            throw new Exception('Contractul nu a fost gasit');
        }
        
        $items = [];
        if ($contract['offer_id']) {
            $stmt = $pdo->prepare("
                SELECT description, quantity, unit_price 
                FROM offer_items 
                WHERE offer_id = ?
            ");
            $stmt->execute([$contract['offer_id']]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($rows as $row) {
                $items[] = [
                    'description' => $row['description'],
                    'quantity' => (float)$row['quantity'],
                    'price' => (float)$row['unit_price']
                ];
            }
        } else {
            $items[] = [
                'description' => $contract['object'] ?? 'Servicii conform contract',
                'quantity' => 1,
                'price' => (float)($contract['total_value'] ?? 0)
            ];
        }
        
        $stmt = $pdo->prepare("SELECT Name, Adress FROM companies WHERE CUI = ?");
        $stmt->execute([$contract['company_cui']]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'client_cif' => $contract['company_cui'],
                'client_name' => $company['Name'] ?? '',
                'client_address' => $company['Adress'] ?? '',
                'items' => $items,
                'sourceType' => 'contract',
                'sourceId' => $contractId
            ]
        ]);
        exit;
    }
if ($f === 'get_oblio_settings') {
    try {
        echo json_encode([
            'success' => true,
            'data' => $oblio->getSettings()
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
    if ($f === 'get_company_settings') {
        $cui = $_POST['cui'] ?? '';
        if (!$cui) {
            throw new Exception('CUI lipseste');
        }
        
        $stmt = $pdo->prepare("
            SELECT vat_payer, default_invoice_series, default_proforma_series 
            FROM companies 
            WHERE CUI = ?
        ");
        $stmt->execute([$cui]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$settings) {
            $settings = [
                'vat_payer' => 1,
                'default_invoice_series' => 'FACT',
                'default_proforma_series' => 'PROF'
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $settings]);
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Functie necunoscuta: ' . $f]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}