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
            throw new Exception('Tip de fișier nu este suportat');
    }
    
    // Delete uploaded file after processing
    unlink($filePath);
    
    $response['success'] = true;
    $response['imported'] = $imported;
    $response['message'] = "$imported evenimente au fost importate cu succes";
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Import helper functions
function importICSFile($pdo, $filePath, $userId, $overwrite) {
    $content = file_get_contents($filePath);
    $imported = 0;
    
    // Parse ICS file
    preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $content, $events);
    
    foreach ($events[1] as $eventData) {
        $event = parseICSEvent($eventData);
        
        if ($event) {
            // Check if event already exists
            if ($overwrite) {
                $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE user_id = ? AND title = ? AND start = ?");
                $stmt->execute([$userId, $event['title'], $event['start']]);
            }
            
            $stmt = $pdo->prepare("INSERT INTO calendar_events 
                (user_id, type, title, description, start, end, all_day, location, priority, source, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'import', NOW())");
            
            $stmt->execute([
                $userId,
                $event['type'] ?? 'meeting',
                $event['title'],
                $event['description'] ?? '',
                $event['start'],
                $event['end'] ?? null,
                $event['all_day'] ?? 0,
                $event['location'] ?? '',
                $event['priority'] ?? 'medium'
            ]);
            
            $imported++;
        }
    }
    
    return $imported;
}

function parseICSEvent($eventData) {
    $event = [];
    
    // Parse SUMMARY (title)
    if (preg_match('/SUMMARY:(.*?)[\r\n]/s', $eventData, $match)) {
        $event['title'] = trim(str_replace(['\n', '\,', '\;'], ["\n", ',', ';'], $match[1]));
    }
    
    // Parse DTSTART
    if (preg_match('/DTSTART(?:;[^:]*)?:(\d{8}T?\d{0,6}Z?)/s', $eventData, $match)) {
        $event['start'] = parseICSDateTime($match[1]);
        $event['all_day'] = (strlen($match[1]) == 8) ? 1 : 0;
    }
    
    // Parse DTEND
    if (preg_match('/DTEND(?:;[^:]*)?:(\d{8}T?\d{0,6}Z?)/s', $eventData, $match)) {
        $event['end'] = parseICSDateTime($match[1]);
    }
    
    // Parse DESCRIPTION
    if (preg_match('/DESCRIPTION:(.*?)[\r\n]/s', $eventData, $match)) {
        $event['description'] = trim(str_replace(['\n', '\,', '\;'], ["\n", ',', ';'], $match[1]));
    }
    
    // Parse LOCATION
    if (preg_match('/LOCATION:(.*?)[\r\n]/s', $eventData, $match)) {
        $event['location'] = trim(str_replace(['\n', '\,', '\;'], ["\n", ',', ';'], $match[1]));
    }
    
    // Parse CATEGORIES (for type)
    if (preg_match('/CATEGORIES:(.*?)[\r\n]/s', $eventData, $match)) {
        $category = strtolower(trim($match[1]));
        if (in_array($category, ['todo', 'meeting', 'deadline', 'reminder'])) {
            $event['type'] = $category;
        }
    }
    
    return empty($event['title']) ? null : $event;
}

function parseICSDateTime($dateTime) {
    // Format: 20240101T120000Z or 20240101
    if (strlen($dateTime) == 8) {
        // Date only
        return substr($dateTime, 0, 4) . '-' . substr($dateTime, 4, 2) . '-' . substr($dateTime, 6, 2) . ' 00:00:00';
    } else {
        // Date and time
        $dateTime = str_replace(['T', 'Z'], '', $dateTime);
        return substr($dateTime, 0, 4) . '-' . substr($dateTime, 4, 2) . '-' . substr($dateTime, 6, 2) . ' ' .
               substr($dateTime, 8, 2) . ':' . substr($dateTime, 10, 2) . ':' . substr($dateTime, 12, 2);
    }
}

function importCSVFile($pdo, $filePath, $userId, $overwrite) {
    $imported = 0;
    $handle = fopen($filePath, 'r');
    
    // Read header
    $header = fgetcsv($handle);
    if (!$header) {
        fclose($handle);
        throw new Exception('Fișier CSV invalid - nu conține header');
    }
    
    // Normalize headers
    $header = array_map('trim', $header);
    $header = array_map('strtolower', $header);
    
    // Map headers
    $titleIndex = array_search('title', $header) !== false ? array_search('title', $header) : 
                  (array_search('summary', $header) !== false ? array_search('summary', $header) : 0);
    $startIndex = array_search('start', $header) !== false ? array_search('start', $header) : 
                  (array_search('start date', $header) !== false ? array_search('start date', $header) : 1);
    $endIndex = array_search('end', $header) !== false ? array_search('end', $header) : 
                (array_search('end date', $header) !== false ? array_search('end date', $header) : 2);
    $descIndex = array_search('description', $header);
    $typeIndex = array_search('type', $header);
    $locationIndex = array_search('location', $header);
    
    while (($row = fgetcsv($handle)) !== false) {
        if (empty($row[$titleIndex]) || empty($row[$startIndex])) continue;
        
        $title = $row[$titleIndex];
        $start = $row[$startIndex];
        $end = isset($row[$endIndex]) ? $row[$endIndex] : null;
        $description = $descIndex !== false && isset($row[$descIndex]) ? $row[$descIndex] : '';
        $type = $typeIndex !== false && isset($row[$typeIndex]) ? strtolower($row[$typeIndex]) : 'meeting';
        $location = $locationIndex !== false && isset($row[$locationIndex]) ? $row[$locationIndex] : '';
        
        // Validate and format dates
        $start = date('Y-m-d H:i:s', strtotime($start));
        $end = $end ? date('Y-m-d H:i:s', strtotime($end)) : null;
        
        if ($overwrite) {
            $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE user_id = ? AND title = ? AND start = ?");
            $stmt->execute([$userId, $title, $start]);
        }
        
        $stmt = $pdo->prepare("INSERT INTO calendar_events 
            (user_id, type, title, description, start, end, location, source, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'import', NOW())");
        
        $stmt->execute([$userId, $type, $title, $description, $start, $end, $location]);
        $imported++;
    }
    
    fclose($handle);
    return $imported;
}

function importJSONFile($pdo, $filePath, $userId, $overwrite) {
    $content = file_get_contents($filePath);
    $data = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Fișier JSON invalid');
    }
    
    $imported = 0;
    $events = [];
    
    // Handle different JSON structures
    if (isset($data['events']) && is_array($data['events'])) {
        $events = $data['events'];
    } elseif (is_array($data)) {
        $events = $data;
    }
    
    foreach ($events as $event) {
        if (empty($event['title']) || empty($event['start'])) continue;
        
        $title = $event['title'];
        $start = date('Y-m-d H:i:s', strtotime($event['start']));
        $end = !empty($event['end']) ? date('Y-m-d H:i:s', strtotime($event['end'])) : null;
        $description = $event['description'] ?? '';
        $type = !empty($event['type']) ? strtolower($event['type']) : 'meeting';
        $location = $event['location'] ?? '';
        $priority = $event['priority'] ?? 'medium';
        
        if ($overwrite) {
            $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE user_id = ? AND title = ? AND start = ?");
            $stmt->execute([$userId, $title, $start]);
        }
        
        $stmt = $pdo->prepare("INSERT INTO calendar_events 
            (user_id, type, title, description, start, end, location, priority, source, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'import', NOW())");
        
        $stmt->execute([$userId, $type, $title, $description, $start, $end, $location, $priority]);
        $imported++;
    }
    
    return $imported;
}
?>