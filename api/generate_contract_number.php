<?php
// api/generate_contract_number.php
// Generates the next contract number in format: 001/2025, 002/2025, etc.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $current_year = date('Y');
        
        // Find all contract numbers for current user in current year
        $sql = "SELECT contract_number FROM contracts WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $contracts_list = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $max_sequential = 0;
        $year_suffix = "/" . $current_year;

        // Extract the highest number from format NNN/YYYY
        foreach($contracts_list as $num) {
            if (strpos($num, $year_suffix) !== false) {
                $sequential_part = substr($num, 0, strpos($num, $year_suffix));
                // Remove any non-numeric chars (like #, CONTRACT-, etc)
                $numeric_part = (int)preg_replace('/[^0-9]/', '', $sequential_part);
                if ($numeric_part > $max_sequential) {
                    $max_sequential = $numeric_part;
                }
            }
        }

        $next_number = $max_sequential + 1;
        
        // Format: 001/2025, 002/2025, etc.
        $formatted_number = str_pad($next_number, 3, '0', STR_PAD_LEFT) . "/" . $current_year;

        $response['success'] = true;
        $response['contract_number'] = "#".$formatted_number;

    } catch (PDOException $e) {
        $response['error'] = 'Eroare la generarea numărului: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'Metodă invalidă.';
}