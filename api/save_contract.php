<?php
// Creates or updates a contract.
// Input (POST):
//   id (optional for update), company_cui*, offer_id, contract_number*, contract_date* (YYYY-MM-DD),
//   object* (text), special_clauses, total_value*, duration_months*
// Output: { success, id, message | error }
$response['success'] = false;

function n($k,$d=null){ return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d; }

$id              = (int) n('id', 0);
$company_cui     = (int) n('company_cui', 0);
$offer_id_raw    = n('offer_id', '');
$offer_id        = ($offer_id_raw === '' ? null : (int)$offer_id_raw);
$contract_number = n('contract_number', '');
$contract_date   = n('contract_date', '');
$object          = n('object', '');
$special_clauses = n('special_clauses', null);
$total_value_raw = n('total_value', '0');
$duration_raw    = n('duration_months', '12');

$total_value     = is_numeric(str_replace([','],['.'],$total_value_raw)) ? (float)str_replace([','],['.'],$total_value_raw) : 0.0;
$duration_months = ctype_digit($duration_raw) ? (int)$duration_raw : 12;

if ($company_cui <= 0 || $contract_number === '' || $contract_date === '' || $object === '' || $duration_months < 1) {
    $response['error'] = 'Câmpuri obligatorii lipsă.';
    return;
}

// contracts schema reference.
// api router loads $pdo, $user_id, and returns $response JSON.

try {
    if ($id > 0) {
        // Update only own contract
        $sql = "UPDATE contracts
                SET company_cui = :company_cui,
                    offer_id = :offer_id,
                    contract_number = :contract_number,
                    contract_date = :contract_date,
                    `object` = :object,
                    special_clauses = :special_clauses,
                    total_value = :total_value,
                    duration_months = :duration_months
                WHERE id = :id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':company_cui'    => $company_cui,
            ':offer_id'       => $offer_id,
            ':contract_number'=> $contract_number,
            ':contract_date'  => $contract_date,
            ':object'         => $object,
            ':special_clauses'=> $special_clauses,
            ':total_value'    => $total_value,
            ':duration_months'=> $duration_months,
            ':id'             => $id,
            ':user_id'        => $user_id
        ]);

        // Treat no-change as success if record exists
        if ($stmt->rowCount() === 0) {
            $chk = $pdo->prepare("SELECT id FROM contracts WHERE id = :id AND user_id = :user_id");
            $chk->execute([':id'=>$id, ':user_id'=>$user_id]);
            if (!$chk->fetch()) {
                $response['error'] = 'Contractul nu există sau nu aparține utilizatorului.';
                return;
            }
        }

        $response['success'] = true;
        $response['id'] = $id;
        $response['message'] = 'Contract salvat.';
    } else {
        // Insert
        $sql = "INSERT INTO contracts
                (user_id, company_cui, offer_id, contract_number, contract_date, `object`, special_clauses, total_value, duration_months)
                VALUES
                (:user_id, :company_cui, :offer_id, :contract_number, :contract_date, :object, :special_clauses, :total_value, :duration_months)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id'        => $user_id,
            ':company_cui'    => $company_cui,
            ':offer_id'       => $offer_id,
            ':contract_number'=> $contract_number,
            ':contract_date'  => $contract_date,
            ':object'         => $object,
            ':special_clauses'=> $special_clauses,
            ':total_value'    => $total_value,
            ':duration_months'=> $duration_months
        ]);
        $response['success'] = true;
        $response['id'] = (int)$pdo->lastInsertId();
        $response['message'] = 'Contract creat.';
    }
} catch (PDOException $e) {
    // unique contract_number guard
    if ((int)$e->getCode() === 23000) {
        $response['error'] = 'Număr de contract existent.';
    } else {
        $response['error'] = 'DB error: '.$e->getMessage();
    }
}
