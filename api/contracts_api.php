<?php
// api/contracts_api.php
session_start();
require_once '../config.php';
require_once '../db.php';

header('Content-Type: application/json');

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$response = ['success' => false];

if (!$user_id) {
    $response['error'] = 'Neautorizat';
    echo json_encode($response);
    exit;
}

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            // Get single contract
            $id = (int)($_GET['id'] ?? 0);
            
            if (!$id) {
                $response['error'] = 'ID invalid';
                break;
            }
            
            $stmt = $pdo->prepare("SELECT c.*, co.Name as company_name, co.Reg as company_reg, co.Adress as company_address
                FROM contracts c
                LEFT JOIN companies co ON c.company_cui = co.CUI
                WHERE c.id = ? AND c.user_id = ?");
            $stmt->execute([$id, $user_id]);
            
            $contract = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($contract) {
                $response['success'] = true;
                $response['data'] = $contract;
            } else {
                $response['error'] = 'Contract nu a fost găsit';
            }
            break;
            
        case 'create':
            // Create new contract
            $contract_number = trim($_POST['contract_number'] ?? '');
            $contract_date = $_POST['contract_date'] ?? '';
            $company_cui = (int)($_POST['company_cui'] ?? 0);
            $object = trim($_POST['object'] ?? '');
            $special_clauses = trim($_POST['special_clauses'] ?? '');
            $total_value = (float)($_POST['total_value'] ?? 0);
            $duration_months = (int)($_POST['duration_months'] ?? 12);
            $vat_series = trim($_POST['vat_series'] ?? 'FACT');
            $offer_id = !empty($_POST['offer_id']) ? (int)$_POST['offer_id'] : null;
            
            // Validation
            if (empty($contract_number)) {
                $response['error'] = 'Numărul contractului este obligatoriu';
                break;
            }
            
            if (empty($contract_date)) {
                $response['error'] = 'Data contractului este obligatorie';
                break;
            }
            
            if (!$company_cui) {
                $response['error'] = 'Compania este obligatorie';
                break;
            }
            
            if (empty($object)) {
                $response['error'] = 'Obiectul contractului este obligatoriu';
                break;
            }
            
            if ($duration_months < 1) {
                $response['error'] = 'Durata trebuie să fie de cel puțin 1 lună';
                break;
            }
            
            // Check if contract number already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contracts WHERE contract_number = ? AND user_id = ?");
            $stmt->execute([$contract_number, $user_id]);
            
            if ($stmt->fetchColumn() > 0) {
                $response['error'] = 'Există deja un contract cu acest număr';
                break;
            }
            
            // Check if company exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE CUI = ?");
            $stmt->execute([$company_cui]);
            
            if ($stmt->fetchColumn() == 0) {
                $response['error'] = 'Compania selectată nu există';
                break;
            }
            
            // Insert contract
            $stmt = $pdo->prepare("INSERT INTO contracts 
                (user_id, company_cui, offer_id, contract_number, contract_date, object, 
                 special_clauses, total_value, duration_months, vat_series, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->execute([
                $user_id,
                $company_cui,
                $offer_id,
                $contract_number,
                $contract_date,
                $object,
                $special_clauses,
                $total_value,
                $duration_months,
                $vat_series
            ]);
            
            $response['success'] = true;
            $response['message'] = 'Contract creat cu succes';
            $response['id'] = $pdo->lastInsertId();
            break;
            
        case 'update':
            // Update existing contract
            $id = (int)($_POST['id'] ?? 0);
            $contract_number = trim($_POST['contract_number'] ?? '');
            $contract_date = $_POST['contract_date'] ?? '';
            $company_cui = (int)($_POST['company_cui'] ?? 0);
            $object = trim($_POST['object'] ?? '');
            $special_clauses = trim($_POST['special_clauses'] ?? '');
            $total_value = (float)($_POST['total_value'] ?? 0);
            $duration_months = (int)($_POST['duration_months'] ?? 12);
            $vat_series = trim($_POST['vat_series'] ?? 'FACT');
            $offer_id = !empty($_POST['offer_id']) ? (int)$_POST['offer_id'] : null;
            
            // Validation
            if (!$id) {
                $response['error'] = 'ID invalid';
                break;
            }
            
            if (empty($contract_number)) {
                $response['error'] = 'Numărul contractului este obligatoriu';
                break;
            }
            
            if (empty($contract_date)) {
                $response['error'] = 'Data contractului este obligatorie';
                break;
            }
            
            if (!$company_cui) {
                $response['error'] = 'Compania este obligatorie';
                break;
            }
            
            if (empty($object)) {
                $response['error'] = 'Obiectul contractului este obligatoriu';
                break;
            }
            
            if ($duration_months < 1) {
                $response['error'] = 'Durata trebuie să fie de cel puțin 1 lună';
                break;
            }
            
            // Check if contract exists and belongs to user
            $stmt = $pdo->prepare("SELECT id FROM contracts WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            
            if (!$stmt->fetch()) {
                $response['error'] = 'Contract nu a fost găsit';
                break;
            }
            
            // Check if contract number is unique (excluding current contract)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contracts 
                WHERE contract_number = ? AND user_id = ? AND id != ?");
            $stmt->execute([$contract_number, $user_id, $id]);
            
            if ($stmt->fetchColumn() > 0) {
                $response['error'] = 'Există deja un contract cu acest număr';
                break;
            }
            
            // Check if company exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE CUI = ?");
            $stmt->execute([$company_cui]);
            
            if ($stmt->fetchColumn() == 0) {
                $response['error'] = 'Compania selectată nu există';
                break;
            }
            
            // Update contract
            $stmt = $pdo->prepare("UPDATE contracts SET 
                company_cui = ?,
                offer_id = ?,
                contract_number = ?,
                contract_date = ?,
                object = ?,
                special_clauses = ?,
                total_value = ?,
                duration_months = ?,
                vat_series = ?
                WHERE id = ? AND user_id = ?");
            
            $stmt->execute([
                $company_cui,
                $offer_id,
                $contract_number,
                $contract_date,
                $object,
                $special_clauses,
                $total_value,
                $duration_months,
                $vat_series,
                $id,
                $user_id
            ]);
            
            $response['success'] = true;
            $response['message'] = 'Contract actualizat cu succes';
            break;
            
        case 'delete':
            // Delete contract
            $id = (int)($_POST['id'] ?? 0);
            
            if (!$id) {
                $response['error'] = 'ID invalid';
                break;
            }
            
            // Check if contract exists and belongs to user
            $stmt = $pdo->prepare("SELECT id FROM contracts WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            
            if (!$stmt->fetch()) {
                $response['error'] = 'Contract nu a fost găsit';
                break;
            }
            
            // Delete contract
            $stmt = $pdo->prepare("DELETE FROM contracts WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            
            $response['success'] = true;
            $response['message'] = 'Contract șters cu succes';
            break;
            
        case 'list':
            // List all contracts
            $filter = $_GET['filter'] ?? 'all';
            $search = $_GET['search'] ?? '';
            
            $whereClause = "user_id = ?";
            $params = [$user_id];
            
            switch ($filter) {
                case 'active':
                    $whereClause .= " AND DATE_ADD(contract_date, INTERVAL duration_months MONTH) >= CURDATE()";
                    break;
                case 'expired':
                    $whereClause .= " AND DATE_ADD(contract_date, INTERVAL duration_months MONTH) < CURDATE()";
                    break;
                case 'expiring':
                    $whereClause .= " AND DATE_ADD(contract_date, INTERVAL duration_months MONTH) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)";
                    break;
            }
            
            if (!empty($search)) {
                $whereClause .= " AND (contract_number LIKE ? OR object LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $stmt = $pdo->prepare("SELECT c.*, co.Name as company_name, co.CUI as company_cui_full,
                DATE_ADD(c.contract_date, INTERVAL c.duration_months MONTH) as end_date,
                DATEDIFF(DATE_ADD(c.contract_date, INTERVAL c.duration_months MONTH), CURDATE()) as days_remaining
                FROM contracts c
                LEFT JOIN companies co ON c.company_cui = co.CUI
                WHERE $whereClause
                ORDER BY c.contract_date DESC");
            
            $stmt->execute($params);
            $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $contracts;
            break;
            
        case 'stats':
            // Get statistics
            $stmt = $pdo->prepare("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN DATE_ADD(contract_date, INTERVAL duration_months MONTH) >= CURDATE() THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN DATE_ADD(contract_date, INTERVAL duration_months MONTH) < CURDATE() THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN DATE_ADD(contract_date, INTERVAL duration_months MONTH) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN 1 ELSE 0 END) as expiring_soon,
                SUM(total_value) as total_value
                FROM contracts WHERE user_id = ?");
            
            $stmt->execute([$user_id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $stats;
            break;
            
        default:
            $response['error'] = 'Acțiune invalidă';
    }
    
} catch (PDOException $e) {
    $response['error'] = 'Eroare bază de date: ' . $e->getMessage();
}

echo json_encode($response);