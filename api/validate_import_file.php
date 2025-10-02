<?php
// api/validate_import_file.php
try {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Fișier nu a fost uploadat corect');
    }
    
    $file = $_FILES['file'];
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if ($file['size'] > $maxSize) {
        throw new Exception('Fișierul este prea mare. Mărimea maximă permisă este 10MB.');
    }
    
    $allowedTypes = ['ics', 'csv', 'json'];
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Tip de fișier nu este suportat. Tipuri permise: ' . implode(', ', $allowedTypes));
    }
    
    // Quick validation based on file type
    $tmpPath = $file['tmp_name'];
    $previewData = [];
    
    switch($fileType) {
        case 'ics':
            $content = file_get_contents($tmpPath);
            if (strpos($content, 'BEGIN:VCALENDAR') === false) {
                throw new Exception('Fișier ICS invalid - nu conține structura de calendar validă');
            }
            $eventCount = substr_count($content, 'BEGIN:VEVENT');
            $previewData = ['event_count' => $eventCount, 'format' => 'iCalendar'];
            break;
            
        case 'csv':
            $handle = fopen($tmpPath, 'r');
            $header = fgetcsv($handle);
            $lineCount = 0;
            while (fgetcsv($handle) !== FALSE) $lineCount++;
            fclose($handle);
            $previewData = ['event_count' => $lineCount, 'headers' => $header, 'format' => 'CSV'];
            break;
            
        case 'json':
            $content = file_get_contents($tmpPath);
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Fișier JSON invalid');
            }
            $eventCount = 0;
            if (isset($data['events'])) {
                $eventCount = count($data['events']);
            } elseif (is_array($data)) {
                $eventCount = count($data);
            }
            $previewData = ['event_count' => $eventCount, 'format' => 'JSON'];
            break;
    }
    
    $response['success'] = true;
    $response['file_type'] = $fileType;
    $response['file_size'] = $file['size'];
    $response['preview'] = $previewData;
    $response['message'] = 'Fișier valid pentru import';
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}
?>