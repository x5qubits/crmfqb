<?php
require 'config.php';
require 'db.php';
$response = array();
$response['success'] = false;
 header('Content-Type: application/json; charset=utf-8');
$f = "";
 
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : -1;

if (isset($_GET['f'])) {
    $f = Z_Secure($_GET['f'], 0);
}
/**
 * Oblio API Handlers for api.php
 * Updated for Oblio API 2025
 * This file should be included in api.php AFTER $pdo is defined
 */

// Verify prerequisites
if (!isset($pdo) || !($pdo instanceof PDO)) {
    error_log("api_oblio_handlers.php: PDO not available");
    return;
}

if (!isset($f) || !isset($user_id)) {
    return;
}

// Load Oblio API
require_once __DIR__ . '/oblio_api.php';

// Initialize Oblio
try {
    $oblio = new OblioAPI($pdo);
} catch (Exception $e) {
    error_log("Failed to initialize Oblio: " . $e->getMessage());
    $oblio = null;
}

// ==================== SETTINGS ====================

if ($f === 'save_oblio_settings') {
    try {
        $email = trim($_POST['oblio_email'] ?? '');
        $secret = trim($_POST['oblio_secret'] ?? '');
        $company = trim($_POST['oblio_company'] ?? '');
        $cif = trim($_POST['oblio_cif'] ?? '');
        
        if (empty($email) || empty($secret)) {
            echo json_encode(['success' => false, 'error' => 'Email și Secret sunt obligatorii']);
            exit;
        }
        
        if (empty($cif)) {
            echo json_encode(['success' => false, 'error' => 'CIF este obligatoriu']);
            exit;
        }
        
        // Remove RO prefix if present
        $cif = preg_replace('/^RO/i', '', $cif);
        
        $oblio->saveSettings($email, $secret, $company, $cif);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Setări salvate cu succes',
            'cif' => $cif
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
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

// ==================== NOMENCLATURE ====================

if ($f === 'get_oblio_companies') {
    try {
        $companies = $oblio->getCompanies();
        echo json_encode([
            'success' => true,
            'data' => $companies
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($f === 'get_oblio_vat_rates') {
    try {
        $cif = $_GET['cif'] ?? null;
        $vatRates = $oblio->getVatRates($cif);
        echo json_encode([
            'success' => true,
            'data' => $vatRates
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($f === 'get_oblio_series') {
    try {
        $cif = $_GET['cif'] ?? null;
        $series = $oblio->getSeries($cif);
        echo json_encode([
            'success' => true,
            'data' => $series
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($f === 'get_oblio_languages') {
    try {
        $cif = $_GET['cif'] ?? null;
        $languages = $oblio->getLanguages($cif);
        echo json_encode([
            'success' => true,
            'data' => $languages
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($f === 'get_oblio_products') {
    try {
        $cif = $_GET['cif'] ?? null;
        $offset = (int)($_GET['offset'] ?? 0);
        $products = $oblio->getProducts($cif, $offset);
        echo json_encode([
            'success' => true,
            'data' => $products
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ==================== CLIENTS ====================

if ($f === 'sync_clients_from_oblio') {
    try {
        $synced = $oblio->syncClientsFromOblio();
        echo json_encode([
            'success' => true,
            'synced' => $synced,
            'message' => "Sincronizat $synced clienți din Oblio"
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($f === 'get_oblio_clients') {
    try {
        $cif = $_GET['cif'] ?? null;
        $name = $_GET['name'] ?? null;
        $clientCif = $_GET['clientCif'] ?? null;
        $offset = (int)($_GET['offset'] ?? 0);
        
        $clients = $oblio->getClients($cif, $name, $clientCif, $offset);
        echo json_encode([
            'success' => true,
            'data' => $clients
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ==================== INVOICES ====================

if ($f === 'create_oblio_invoice') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            throw new Exception('Date invalide');
        }
        
        // Ensure CIF is set
        if (!isset($data['cif'])) {
            $settings = $oblio->getSettings();
            $data['cif'] = 'RO' . $settings['cif'];
        }
        
        $result = $oblio->createInvoice($data);
        
        echo json_encode([
            'success' => true,
            'data' => $result,
            'message' => 'Factură creată cu succes'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($f === 'create_oblio_proforma') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            throw new Exception('Date invalide');
        }
        
        // Ensure CIF is set
        if (!isset($data['cif'])) {
            $settings = $oblio->getSettings();
            $data['cif'] = 'RO' . $settings['cif'];
        }
        
        $result = $oblio->createProforma($data);
        
        echo json_encode([
            'success' => true,
            'data' => $result,
            'message' => 'Proformă creată cu succes'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($f === 'get_oblio_invoice') {
    try {
        $seriesName = $_GET['series'] ?? '';
        $number = (int)($_GET['number'] ?? 0);
        $cif = $_GET['cif'] ?? null;
        
        if (!$seriesName || !$number) {
            throw new Exception('Serie și număr sunt obligatorii');
        }
        
        $invoice = $oblio->getInvoice($seriesName, $number, $cif);
        
        echo json_encode([
            'success' => true,
            'data' => $invoice
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($f === 'list_oblio_invoices') {
    try {
        $filters = [];
        
        // Parse filters from GET/POST
        if (isset($_REQUEST['seriesName'])) $filters['seriesName'] = $_REQUEST['seriesName'];
        if (isset($_REQUEST['number'])) $filters['number'] = (int)$_REQUEST['number'];
        if (isset($_REQUEST['year'])) {
            $year = (int)$_REQUEST['year'];
            $filters['issuedAfter'] = "$year-01-01";
            $filters['issuedBefore'] = "$year-12-31";
        }
        if (isset($_REQUEST['month'])) {
            $month = (int)$_REQUEST['month'];
            $year = (int)($_REQUEST['year'] ?? date('Y'));
            $filters['issuedAfter'] = sprintf('%04d-%02d-01', $year, $month);
            $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $filters['issuedBefore'] = sprintf('%04d-%02d-%02d', $year, $month, $lastDay);
        }
        if (isset($_REQUEST['issuedAfter'])) $filters['issuedAfter'] = $_REQUEST['issuedAfter'];
        if (isset($_REQUEST['issuedBefore'])) $filters['issuedBefore'] = $_REQUEST['issuedBefore'];
        if (isset($_REQUEST['draft'])) $filters['draft'] = (int)$_REQUEST['draft'];
        if (isset($_REQUEST['canceled'])) $filters['canceled'] = (int)$_REQUEST['canceled'];
        if (isset($_REQUEST['limitPerPage'])) $filters['limitPerPage'] = min(100, (int)$_REQUEST['limitPerPage']);
        if (isset($_REQUEST['offset'])) $filters['offset'] = (int)$_REQUEST['offset'];
        
        $invoices = $oblio->listInvoices($filters);
        
        echo json_encode([
            'success' => true,
            'data' => $invoices,
            'count' => count($invoices)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($f === 'cancel_oblio_invoice') {
    try {
        $seriesName = $_POST['series'] ?? '';
        $number = (int)($_POST['number'] ?? 0);
        $cif = $_POST['cif'] ?? null;
        
        if (!$seriesName || !$number) {
            throw new Exception('Serie și număr sunt obligatorii');
        }
        
        $result = $oblio->cancelInvoice($seriesName, $number, $cif);
        
        echo json_encode([
            'success' => true,
            'data' => $result,
            'message' => 'Factură anulată cu succes'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($f === 'restore_oblio_invoice') {
    try {
        $seriesName = $_POST['series'] ?? '';
        $number = (int)($_POST['number'] ?? 0);
        $cif = $_POST['cif'] ?? null;
        
        if (!$seriesName || !$number) {
            throw new Exception('Serie și număr sunt obligatorii');
        }
        
        $result = $oblio->restoreInvoice($seriesName, $number, $cif);
        
        echo json_encode([
            'success' => true,
            'data' => $result,
            'message' => 'Factură restaurată cu succes'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
if ($f === 'get_company_invoices') {
    try {
        $companyCui = (int)($_POST['company_cui'] ?? $_GET['company_cui'] ?? 0);
        
        if (!$companyCui) {
            echo json_encode(['success' => false, 'error' => 'CUI companie lipsă']);
            exit;
        }
        
        $query = "SELECT * FROM oblio_invoices WHERE user_id = ? AND client_cif = ? ";
        $params = [$user_id, $companyCui];
        
        $query .= "ORDER BY date DESC, number DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $invoices
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
if ($f === 'delete_oblio_invoice') {
    try {
        $seriesName = $_POST['series'] ?? '';
        $number = (int)($_POST['number'] ?? 0);
        $cif = $_POST['cif'] ?? null;
        
        if (!$seriesName || !$number) {
            throw new Exception('Serie și număr sunt obligatorii');
        }
        
        $result = $oblio->deleteInvoice($seriesName, $number, $cif);
        
        echo json_encode([
            'success' => true,
            'data' => $result,
            'message' => 'Factură ștearsă cu succes'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($f === 'collect_oblio_invoice') {
    try {
        $seriesName = $_POST['series'] ?? '';
        $number = (int)($_POST['number'] ?? 0);
        $cif = $_POST['cif'] ?? null;
        
        $collectData = [
            'type' => $_POST['collect_type'] ?? 'Ordin de plata',
            'documentNumber' => $_POST['document_number'] ?? '',
            'value' => isset($_POST['value']) ? (float)$_POST['value'] : null,
            'issueDate' => $_POST['issue_date'] ?? date('Y-m-d')
        ];
        
        if (!$seriesName || !$number) {
            throw new Exception('Serie și număr sunt obligatorii');
        }
        
        $result = $oblio->collectInvoice($seriesName, $number, $collectData, $cif);
        
        echo json_encode([
            'success' => true,
            'data' => $result,
            'message' => 'Încasare înregistrată cu succes'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($f === 'sync_invoices_from_oblio') {
    try {
        $year = isset($_POST['year']) ? (int)$_POST['year'] : date('Y');
        $month = isset($_POST['month']) ? (int)$_POST['month'] : null;
        
        $synced = $oblio->syncInvoicesFromOblio($year, $month);
        
        echo json_encode([
            'success' => true,
            'synced' => $synced,
            'message' => "Sincronizate $synced facturi din Oblio"
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ==================== E-INVOICE (SPV) ====================

if ($f === 'send_einvoice_to_spv') {
    try {
        $seriesName = $_POST['series'] ?? '';
        $number = (int)($_POST['number'] ?? 0);
        $cif = $_POST['cif'] ?? null;
        
        if (!$seriesName || !$number) {
            throw new Exception('Serie și număr sunt obligatorii');
        }
        
        $result = $oblio->sendEInvoice($seriesName, $number, $cif);
        
        echo json_encode([
            'success' => true,
            'data' => $result,
            'message' => $result['text'] ?? 'e-Factură trimisă în SPV'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($f === 'download_einvoice_archive') {
    try {
        $seriesName = $_GET['series'] ?? '';
        $number = (int)($_GET['number'] ?? 0);
        $cif = $_GET['cif'] ?? null;
        
        if (!$seriesName || !$number) {
            throw new Exception('Serie și număr sunt obligatorii');
        }
        
        $downloadUrl = $oblio->downloadEInvoiceArchive($seriesName, $number, $cif);
        
        // Redirect to download URL
        header('Location: ' . $downloadUrl);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo "Eroare: " . $e->getMessage();
        exit;
    }
}

// ==================== LOCAL DATABASE ====================

if ($f === 'get_local_invoices') {
    try {
        $companyCui = $_POST['company_cui'] ?? $_GET['company_cui'] ?? '';
        
        $query = "SELECT * FROM oblio_invoices WHERE user_id = ? ";
        $params = [$user_id];
        
        if ($companyCui) {
            $query .= "AND company_cui = ? ";
            $params[] = $companyCui;
        }
        
        $query .= "ORDER BY date DESC, number DESC LIMIT 100";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $invoices
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($f === 'get_invoice_details') {
    try {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT * FROM oblio_invoices WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            throw new Exception('Factură negăsită');
        }
        
        echo json_encode([
            'success' => true,
            'data' => $invoice
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($f === 'download_invoice_pdf') {
    try {
        $id = (int)($_GET['id'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT * FROM oblio_invoices WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
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
?>