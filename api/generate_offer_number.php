<?php
// $response este deja definit ca array (success: false)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $current_year = date('Y');
        
        // Caută cel mai mare număr de ofertă din anul curent
        // Presupunând că numărul de ofertă este de forma 'OFERTA-XXXX/YYYY'
        $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(offer_number, '-', -2) AS UNSIGNED)) AS max_number
                FROM offers
                WHERE offer_number LIKE :year_pattern AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        
        // Căutare după pattern-ul YEAR (ex: '%/2025') sau similar
        // Deoarece formatul nu e standardizat, mă bazez doar pe cel mai mare număr găsit, 
        // dar voi forța un format standard în aplicație (ex: 001/2025)
        $stmt->execute([
            ':year_pattern' => '%/' . $current_year,
            ':user_id' => $user_id
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $next_number = 1;

        if ($result && $result['max_number'] !== null) {
            // Extrage numărul dinaintea slash-ului, presupunând formatul NNN/YYYY
            // Dacă numerele sunt stocate ca '100', '101', va lua MAX(CAST(offer_number AS UNSIGNED))
            
            // O metodă mai robustă: extragem toate numerele și vedem care e maxim
            $sql_max = "SELECT offer_number FROM offers WHERE user_id = :user_id AND YEAR(offer_date) = :year";
            $stmt_max = $pdo->prepare($sql_max);
            $stmt_max->execute([':user_id' => $user_id, ':year' => $current_year]);
            $offers_list = $stmt_max->fetchAll(PDO::FETCH_COLUMN);
            
            $max_sequential = 0;
            $year_prefix = "/" . $current_year;

            foreach($offers_list as $num) {
                if (strpos($num, $year_prefix) !== false) {
                    $sequential_part = substr($num, 0, strpos($num, $year_prefix));
                    $numeric_part = (int)$sequential_part;
                    if ($numeric_part > $max_sequential) {
                        $max_sequential = $numeric_part;
                    }
                }
            }

            $next_number = $max_sequential + 1;
        }

        // Formatează numărul (ex: 001/2025)
        $formatted_number = str_pad($next_number, 3, '0', STR_PAD_LEFT) . "/" . $current_year;

        $response['success'] = true;
        $response['offer_number'] = $formatted_number;

    } catch (PDOException $e) {
        $response['error'] = 'Eroare la generarea numărului de ofertă: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'Metodă de cerere invalidă.';
}
