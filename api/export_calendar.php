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
    $response['event_count'] = count($events);
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

function exportToICS($events, $filePath) {
    $icsContent = "BEGIN:VCALENDAR\r\n";
    $icsContent .= "VERSION:2.0\r\n";
    $icsContent .= "PRODID:-//Calendar Export//EN\r\n";
    $icsContent .= "CALSCALE:GREGORIAN\r\n";
    $icsContent .= "METHOD:PUBLISH\r\n";
    
    foreach($events as $event) {
        $icsContent .= "BEGIN:VEVENT\r\n";
        $icsContent .= "UID:" . ($event['uid'] ?? 'event-' . $event['id'] . '@calendar') . "\r\n";
        $icsContent .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        $icsContent .= "DTSTART:" . convertToICSDateTime($event['start'], $event['all_day']) . "\r\n";
        
        if ($event['end']) {
            $icsContent .= "DTEND:" . convertToICSDateTime($event['end'], $event['all_day']) . "\r\n";
        }
        
        $icsContent .= "SUMMARY:" . escapeICSText($event['title']) . "\r\n";
        
        if (!empty($event['description'])) {
            $icsContent .= "DESCRIPTION:" . escapeICSText($event['description']) . "\r\n";
        }
        
        if (!empty($event['location'])) {
            $icsContent .= "LOCATION:" . escapeICSText($event['location']) . "\r\n";
        }
        
        $icsContent .= "CATEGORIES:" . strtoupper($event['type']) . "\r\n";
        
        if (!empty($event['priority'])) {
            // Priority mapping: urgent=1, high=3, medium=5, low=7
            $priorityMap = ['urgent' => 1, 'high' => 3, 'medium' => 5, 'low' => 7];
            $priority = $priorityMap[$event['priority']] ?? 5;
            $icsContent .= "PRIORITY:$priority\r\n";
        }
        
        $icsContent .= "CREATED:" . gmdate('Ymd\THis\Z', strtotime($event['created_at'])) . "\r\n";
        
        if (!empty($event['updated_at'])) {
            $icsContent .= "LAST-MODIFIED:" . gmdate('Ymd\THis\Z', strtotime($event['updated_at'])) . "\r\n";
        }
        
        $icsContent .= "END:VEVENT\r\n";
    }
    
    $icsContent .= "END:VCALENDAR\r\n";
    
    file_put_contents($filePath, $icsContent);
    return $filePath;
}

function exportToCSV($events, $filePath) {
    $handle = fopen($filePath, 'w');
    
    // Write BOM for UTF-8
    fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
    
    $header = ['ID', 'Type', 'Title', 'Description', 'Start', 'End', 'All Day', 
               'Location', 'Attendees', 'Priority', 'Recurring', 'Created'];
    fputcsv($handle, $header);
    
    foreach($events as $event) {
        $row = [
            $event['id'],
            $event['type'],
            $event['title'],
            $event['description'] ?? '',
            $event['start'],
            $event['end'] ?? '',
            $event['all_day'] ? 'Yes' : 'No',
            $event['location'] ?? '',
            $event['attendees'] ?? '',
            $event['priority'],
            $event['recurring'] ? 'Yes' : 'No',
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
        'format_version' => '1.0',
        'events' => $events
    ];
    
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $filePath;
}

function exportToPDF($events, $filePath) {
    $html = '<!DOCTYPE html><html><head>';
    $html .= '<meta charset="UTF-8">';
    $html .= '<title>Calendar Export</title>';
    $html .= '<style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5;
        }
        .header {
            background: #4CAF50;
            color: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .header h1 { margin: 0; }
        .stats {
            background: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .event { 
            border: 1px solid #ddd; 
            margin: 10px 0; 
            padding: 15px; 
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .event-title { 
            font-weight: bold; 
            font-size: 18px; 
            color: #333;
            margin-bottom: 8px;
        }
        .event-meta { 
            color: #666; 
            font-size: 13px; 
            margin: 5px 0;
        }
        .event-description { 
            margin: 10px 0; 
            color: #444;
            line-height: 1.5;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-right: 5px;
        }
        .badge-type { background: #2196F3; color: white; }
        .badge-priority { background: #FF9800; color: white; }
        .badge-urgent { background: #F44336; color: white; }
        @media print {
            body { background: white; }
            .event { page-break-inside: avoid; }
        }
    </style></head><body>';
    
    $html .= '<div class="header">';
    $html .= '<h1>Calendar Export</h1>';
    $html .= '<p>Generated: ' . date('d.m.Y H:i:s') . '</p>';
    $html .= '</div>';
    
    $html .= '<div class="stats">';
    $html .= '<strong>Total Events:</strong> ' . count($events);
    $html .= '</div>';
    
    foreach($events as $event) {
        $html .= '<div class="event">';
        $html .= '<div class="event-title">' . htmlspecialchars($event['title']) . '</div>';
        
        $html .= '<div class="event-meta">';
        $html .= '<span class="badge badge-type">' . ucfirst($event['type']) . '</span>';
        
        $priorityClass = $event['priority'] === 'urgent' ? 'badge-urgent' : 'badge-priority';
        $html .= '<span class="badge ' . $priorityClass . '">' . ucfirst($event['priority']) . '</span>';
        $html .= '</div>';
        
        $html .= '<div class="event-meta">';
        $html .= '<strong>📅 Start:</strong> ' . date('d.m.Y H:i', strtotime($event['start']));
        if (!empty($event['end'])) {
            $html .= ' <strong>→ End:</strong> ' . date('d.m.Y H:i', strtotime($event['end']));
        }
        $html .= '</div>';
        
        if (!empty($event['description'])) {
            $html .= '<div class="event-description">' . nl2br(htmlspecialchars($event['description'])) . '</div>';
        }
        
        if (!empty($event['location'])) {
            $html .= '<div class="event-meta"><strong>📍 Location:</strong> ' . htmlspecialchars($event['location']) . '</div>';
        }
        
        if (!empty($event['attendees'])) {
            $html .= '<div class="event-meta"><strong>👥 Attendees:</strong> ' . htmlspecialchars($event['attendees']) . '</div>';
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
        $date->setTimezone(new DateTimeZone('UTC'));
        return $date->format('Ymd\THis\Z');
    }
}

function escapeICSText($text) {
    // Escape special characters in ICS format
    $text = str_replace(['\\', ';', ',', "\n", "\r"], ['\\\\', '\\;', '\\,', '\\n', ''], $text);
    return $text;
}
?>