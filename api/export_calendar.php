<?php
// api/export_calendar.php
try {
    $format = $_POST['format'] ?? 'ics';
    $period = $_POST['period'] ?? 'all';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $types = $_POST['types'] ?? [];
    
    $conditions = ["user_id = ?"];
    $params = [$user_id];
    
    if ($period === 'month') {
        $conditions[] = "MONTH(start) = MONTH(NOW()) AND YEAR(start) = YEAR(NOW())";
    } elseif ($period === 'year') {
        $conditions[] = "YEAR(start) = YEAR(NOW())";
    } elseif ($period === 'custom' && $startDate && $endDate) {
        $conditions[] = "DATE(start) BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
    }
    
    if (!empty($types)) {
        $placeholders = str_repeat('?,', count($types) - 1) . '?';
        $conditions[] = "type IN ($placeholders)";
        $params = array_merge($params, $types);
    }
    
    $sql = "SELECT * FROM calendar_events WHERE " . implode(' AND ', $conditions) . " ORDER BY start ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $exportDir = 'exports/calendar/';
    if (!file_exists($exportDir)) {
        mkdir($exportDir, 0755, true);
    }
    
    $filename = '';
    $fileUrl = '';
    
    switch($format) {
        case 'ics':
            $filename = 'calendar_' . date('Y-m-d_H-i-s') . '.ics';
            $fileUrl = exportToICS($events, $exportDir . $filename);
            break;
        case 'csv':
            $filename = 'calendar_' . date('Y-m-d_H-i-s') . '.csv';
            $fileUrl = exportToCSV($events, $exportDir . $filename);
            break;
        case 'json':
            $filename = 'calendar_' . date('Y-m-d_H-i-s') . '.json';
            $fileUrl = exportToJSON($events, $exportDir . $filename);
            break;
        case 'pdf':
            $filename = 'calendar_' . date('Y-m-d_H-i-s') . '.html';
            $fileUrl = exportToPDF($events, $exportDir . $filename);
            break;
        default:
            throw new Exception('Format de export nu este suportat');
    }
    
    $response['success'] = true;
    $response['filename'] = $filename;
    $response['file_url'] = $fileUrl;
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

function exportToICS($events, $filePath) {
    $icsContent = "BEGIN:VCALENDAR\r\n";
    $icsContent .= "VERSION:2.0\r\n";
    $icsContent .= "PRODID:-//Calendar Export//EN\r\n";
    $icsContent .= "CALSCALE:GREGORIAN\r\n";
    
    foreach($events as $event) {
        $icsContent .= "BEGIN:VEVENT\r\n";
        $icsContent .= "UID:" . ($event['uid'] ?? uniqid()) . "\r\n";
        $icsContent .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        $icsContent .= "DTSTART:" . convertToICSDateTime($event['start'], $event['all_day']) . "\r\n";
        
        if ($event['end']) {
            $icsContent .= "DTEND:" . convertToICSDateTime($event['end'], $event['all_day']) . "\r\n";
        }
        
        $icsContent .= "SUMMARY:" . escapeICSText($event['title']) . "\r\n";
        
        if ($event['description']) {
            $icsContent .= "DESCRIPTION:" . escapeICSText($event['description']) . "\r\n";
        }
        
        if ($event['location']) {
            $icsContent .= "LOCATION:" . escapeICSText($event['location']) . "\r\n";
        }
        
        $icsContent .= "CATEGORIES:" . strtoupper($event['type']) . "\r\n";
        $icsContent .= "CREATED:" . gmdate('Ymd\THis\Z', strtotime($event['created_at'])) . "\r\n";
        $icsContent .= "END:VEVENT\r\n";
    }
    
    $icsContent .= "END:VCALENDAR\r\n";
    
    file_put_contents($filePath, $icsContent);
    return $filePath;
}

function exportToCSV($events, $filePath) {
    $handle = fopen($filePath, 'w');
    
    $header = ['ID', 'Type', 'Title', 'Description', 'Start', 'End', 'All Day', 
               'Location', 'Attendees', 'Priority', 'Created'];
    fputcsv($handle, $header);
    
    foreach($events as $event) {
        $row = [
            $event['id'],
            $event['type'],
            $event['title'],
            $event['description'],
            $event['start'],
            $event['end'],
            $event['all_day'] ? 'Yes' : 'No',
            $event['location'],
            $event['attendees'],
            $event['priority'],
            $event['created_at']
        ];
        fputcsv($handle, $row);
    }
    
    fclose($handle);
    return $filePath;
}

function exportToJSON($events, $filePath) {
    $data = [
        'export_date' => date('Y-m-d H:i:s'),
        'total_events' => count($events),
        'events' => $events
    ];
    
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $filePath;
}

function exportToPDF($events, $filePath) {
    $html = '<html><head><title>Calendar Export</title>';
    $html .= '<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .event { border: 1px solid #ddd; margin: 10px 0; padding: 10px; }
        .event-title { font-weight: bold; font-size: 16px; }
        .event-meta { color: #666; font-size: 12px; }
        .event-description { margin: 5px 0; }
    </style></head><body>';
    
    $html .= '<h1>Calendar Export - ' . date('Y-m-d H:i:s') . '</h1>';
    $html .= '<p>Total Events: ' . count($events) . '</p>';
    
    foreach($events as $event) {
        $html .= '<div class="event">';
        $html .= '<div class="event-title">' . htmlspecialchars($event['title']) . '</div>';
        $html .= '<div class="event-meta">';
        $html .= 'Type: ' . ucfirst($event['type']) . ' | ';
        $html .= 'Date: ' . date('d.m.Y H:i', strtotime($event['start']));
        if ($event['end']) {
            $html .= ' - ' . date('d.m.Y H:i', strtotime($event['end']));
        }
        $html .= ' | Priority: ' . ucfirst($event['priority']);
        $html .= '</div>';
        
        if ($event['description']) {
            $html .= '<div class="event-description">' . nl2br(htmlspecialchars($event['description'])) . '</div>';
        }
        
        if ($event['location']) {
            $html .= '<div class="event-meta">Location: ' . htmlspecialchars($event['location']) . '</div>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</body></html>';
    
    file_put_contents($filePath, $html);
    return $filePath;
}

function convertToICSDateTime($dateTime, $allDay = false) {
    $date = new DateTime($dateTime);
    
    if ($allDay) {
        return $date->format('Ymd');
    } else {
        return $date->format('Ymd\THis\Z');
    }
}

function escapeICSText($text) {
    $text = str_replace(['\\', ';', ',', "\n", "\r"], ['\\\\', '\\;', '\\,', '\\n', ''], $text);
    return $text;
}
?>
