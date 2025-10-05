<?php
// api/delete_company.php
$cui = isset($_POST['cui']) ? (int)$_POST['cui'] : 0;

if (empty($cui)) {
    $response['error'] = 'CUI invalid!';
    return;
}

try {
    // Delete all contacts for this company first
    $stmt = $pdo->prepare("DELETE FROM contacts WHERE companie = :cui");
    $stmt->execute([':cui' => $cui]);
    
    // Delete the company
    $stmt = $pdo->prepare("DELETE FROM companies WHERE CUI = :cui");
    $stmt->execute([':cui' => $cui]);
    
    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Companie ștearsă cu succes!';
    } else {
        $response['error'] = 'Compania nu a fost găsită!';
    }
} catch (Exception $e) {
    $response['error'] = 'Eroare la ștergere: ' . $e->getMessage();
}
