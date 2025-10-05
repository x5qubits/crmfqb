<?php
// api/save_company.php
function post($k,$d=''){ return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d; }
// read
$action  = post('action','add');
$cuiOld  = (int)post('cui_old','0');
$cui     = (int)post('cui','0');                 // CUI stays numeric
$regRaw  = post('reg','');                       // keep as string
$name    = post('name','');
$address = post('address', post('adress',''));

// robust validation (no empty())
if ($cui <= 0 || $name === '' || $address === '') {
    echo json_encode(['success'=>false,'error'=>'Toate câmpurile sunt obligatorii!']); exit;
}

// since DB column `Reg` is INT, strip non-digits for storage
$regDigits = $regRaw;
if ($regDigits === '') $regDigits = '-';         // fallback to 0 if nothing left

try {
    if ($action === 'edit') {
        $cui_old = isset($_POST['cui_old']) ? (int)$_POST['cui_old'] : $cui;
        
        // Check if CUI changed and new CUI already exists
        if ($cui != $cui_old) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE CUI = :cui");
            $stmt->execute([':cui' => $cui]);
            if ($stmt->fetchColumn() > 0) {
                $response['error'] = 'Există deja o companie cu acest CUI!';
                return;
            }
            
            // Update contacts with new CUI
            $stmt = $pdo->prepare("UPDATE contacts SET companie = :new_cui WHERE companie = :old_cui");
            $stmt->execute([':new_cui' => $cui, ':old_cui' => $cui_old]);
        }
        
        $sql = "UPDATE companies SET CUI = :cui, Reg = :reg, Name = :name, Adress = :address WHERE CUI = :cui_old";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cui' => $cui,
            ':reg' => $regDigits,
            ':name' => $name,
            ':address' => $address,
            ':cui_old' => $cui_old
        ]);
        
        $response['success'] = true;
        $response['message'] = 'Companie actualizată cu succes!';
    } else {
        // Check if CUI already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE CUI = :cui");
        $stmt->execute([':cui' => $cui]);
        if ($stmt->fetchColumn() > 0) {
            $response['error'] = 'Există deja o companie cu acest CUI!';
            return;
        }
        
        $sql = "INSERT INTO companies (CUI, Reg, Name, Adress) VALUES (:cui, :reg, :name, :address)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cui' => $cui,
            ':reg' => $regDigits,
            ':name' => $name,
            ':address' => $address
        ]);
        
        $response['success'] = true;
        $response['message'] = 'Companie adăugată cu succes!';
    }
} catch (Exception $e) {
    $response['error'] = 'Eroare la salvare: ' . $e->getMessage();
}