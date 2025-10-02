<?php
// Runs inside api.php. Uses $pdo/$conn, $user_id, $response.
if (!isset($response) || !is_array($response)) $response = ['success'=>false];

$id = isset($_POST['id']) ? (int)$_POST['id'] : (int)($_POST['contract_id'] ?? 0);
if ($id <= 0) { $response['error'] = 'ID invalid.'; return; }

$usePdo = isset($pdo) && $pdo instanceof PDO;

try {
    if ($usePdo) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1) Inspect ownership for precise error
        $chk = $pdo->prepare("SELECT id, user_id FROM contacts WHERE id = :id");
        $chk->execute([':id'=>$id]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);
        if (!$row) { $response['error'] = 'Contract inexistent.'; return; }

        // 2) Delete if owned, or if owner is NULL/0 (legacy rows)
        $st = $pdo->prepare("
            DELETE FROM contacts
             WHERE id = :id
               AND (user_id = :uid OR user_id IS NULL OR user_id = 0)
        ");
        $st->execute([':id'=>$id, ':uid'=>$user_id]);

        if ($st->rowCount() === 0) {
            $response['error'] = 'Fără drepturi pentru acest contract.';
            return;
        }

        $response['success'] = true;
        $response['message'] = 'Contract șters.';
        return;

    } else {
        if (!isset($conn)) { $response['error'] = 'DB connection missing.'; return; }

        // 1) Inspect
        $rs = $conn->query("SELECT id, user_id FROM contacts WHERE id = $id");
        if (!$rs || !$rs->num_rows) { $response['error'] = 'Contract inexistent.'; return; }
        $row = $rs->fetch_assoc();
        $uid = (int)$row['user_id'];

        // 2) Delete with relaxed ownership for NULL/0
        $cond = ($uid === 0) ? " (user_id IS NULL OR user_id = 0)" : " user_id = $user_id";
        $sql = "DELETE FROM contacts WHERE id = $id AND ($cond)";
        if (!$conn->query($sql)) { $response['error'] = 'DB: '.$conn->error; return; }
        if ($conn->affected_rows === 0) { $response['error'] = 'Fără drepturi pentru acest contract.'; return; }

        $response['success'] = true;
        $response['message'] = 'Contract șters.';
        return;
    }
} catch (Throwable $e) {
    $response['error'] = 'Eroare: '.$e->getMessage();
    return;
}
