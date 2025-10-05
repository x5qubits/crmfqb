<?php
// Get details for a single company
$cui = isset($_GET['cui']) ? (int)$_GET['cui'] : 0;

if ($cui <= 0) {
    $response['success'] = false;
    $response['error'] = 'CUI invalid!';
    return;
}

try {
    // Get company info
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE CUI = :cui");
    $stmt->execute([':cui' => $cui]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $response['success'] = false;
        $response['error'] = 'Compania nu a fost găsită!';
        return;
    }
    
    // Get all contacts
    $stmt = $pdo->prepare("
        SELECT 
            co.id as contact_id,
            co.name as contact_name,
            co.phone as contact_phone,
            co.email as contact_email,
            co.role as contact_role,
            co.user_id,
            u.name as user_name
        FROM contacts co
        LEFT JOIN users u ON co.user_id = u.id
        WHERE co.companie = :cui
        ORDER BY co.role ASC, co.name ASC
    ");
    $stmt->execute([':cui' => $cui]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Find admin contact
    $admin_contact = null;
    foreach ($contacts as $contact) {
        if ($contact['contact_role'] == 0) {
            $admin_contact = $contact;
            break;
        }
    }
    
    $response['success'] = true;
    $response['company'] = [
        'CUI' => $company['CUI'],
        'Reg' => $company['Reg'],
        'company_name' => $company['Name'],
        'company_address' => $company['Adress'],
        'contacts' => $contacts,
        'admin_contact' => $admin_contact
    ];
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['error'] = 'Eroare bază de date: ' . $e->getMessage();
}