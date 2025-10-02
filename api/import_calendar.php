<?php
// api/import_calendar.php
try {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Fișier nu a fost uploadat corect');
    }
    
    $file = $_FILES['file'];
    $fileType = $_POST['type'] ?? '';
    $calendar = $_POST['calendar'] ?? 'main';
    $overwrite = ($_POST['overwrite'] ?? '0') === '1';
    
    $uploadDir = 'uploads/calendar/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = uniqid() . '_' . basename($file['name']);
    $filePath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Eroare la salvarea fișierului');
    }
    
    $imported = 0;
    
    switch($fileType) {
        case 'ics':
            $imported = importICSFile($pdo, $filePath, $user_id, $overwrite);
            break;
        case 'csv':
            $imported = importCSVFile($pdo, $filePath, $user_id, $overwrite);
            break;
        case 'json':
            $imported = importJSONFile($pdo, $filePath, $user_id, $overwrite);
            break;
        default:
            throw new Exception('Acțiune nu este suportată');
    }
    
    $response['success'] = true;
    $response['affected'] = $affected;
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}
?>