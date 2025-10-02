<?php
// api/save_contact.php
// Runs inside api.php router. Expects $pdo or $conn, $user_id, and $response (array).

if (!isset($response) || !is_array($response)) { $response = ['success' => false]; }

$action   = $_POST['action']   ?? '';
$id       = (int)($_POST['id'] ?? 0);
$companie = (int)($_POST['companie'] ?? 0);  // company CUI
$role     = (int)($_POST['role'] ?? 0);
$name     = trim((string)($_POST['name']  ?? ''));
$phoneRaw = trim((string)($_POST['phone'] ?? ''));
$email    = trim((string)($_POST['email'] ?? ''));

// Normalize phone: keep leading + and digits only
$phone = preg_replace('/(?!^\+)\D+/', '', $phoneRaw);

// Validate (avoid empty() quirks with "0")
if ($companie <= 0 || $name === '' || $phone === '') {
    $response['success'] = false;
    $response['error']   = 'Toate câmpurile sunt obligatorii!';
    return;
}
if (mb_strlen($phone) > 15) {
    $response['success'] = false;
    $response['error']   = 'Număr de telefon prea lung (max 15).';
    return;
}
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['success'] = false;
    $response['error']   = 'Email invalid.';
    return;
}

$usePdo = isset($pdo) && $pdo instanceof PDO;

try {
    if ($usePdo) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($action === 'edit' && $id > 0) {
            $st = $pdo->prepare("
                UPDATE contacts
                   SET companie = :companie,
                       role     = :role,
                       name     = :name,
                       phone    = :phone,
                       email    = :email
                 WHERE id = :id AND user_id = :uid
            ");
            $st->execute([
                ':companie' => $companie,
                ':role'     => $role,
                ':name'     => $name,
                ':phone'    => $phone,
                ':email'    => $email,
                ':id'       => $id,
                ':uid'      => $user_id
            ]);

            // Zero-row update is OK if the record exists (no changes)
            if ($st->rowCount() === 0) {
                $chk = $pdo->prepare("SELECT id FROM contacts WHERE id = :id AND user_id = :uid");
                $chk->execute([':id'=>$id, ':uid'=>$user_id]);
                if (!$chk->fetch(PDO::FETCH_ASSOC)) {
                    $response['success'] = false;
                    $response['error']   = 'Contact inexistent.';
                    return;
                }
            }

            $response['success'] = true;
            $response['message'] = 'Contact actualizat.';
            return;
        }

        // ADD
        $st = $pdo->prepare("
            INSERT INTO contacts (user_id, companie, role, name, phone, email)
            VALUES (:uid, :companie, :role, :name, :phone, :email)
        ");
        $st->execute([
            ':uid'      => $user_id,
            ':companie' => $companie,
            ':role'     => $role,
            ':name'     => $name,
            ':phone'    => $phone,
            ':email'    => $email
        ]);

        $response['success']   = true;
        $response['message']   = 'Contact adăugat.';
        $response['insert_id'] = (int)$pdo->lastInsertId();
        return;

    } else {
        // mysqli fallback
        if (!isset($conn)) {
            $response['success'] = false;
            $response['error']   = 'DB connection missing';
            return;
        }

        $nameEsc  = $conn->real_escape_string($name);
        $phoneEsc = $conn->real_escape_string($phone);
        $emailEsc = $conn->real_escape_string($email);

        if ($action === 'edit' && $id > 0) {
            $sql = "
                UPDATE contacts
                   SET companie = $companie,
                       role     = $role,
                       name     = '$nameEsc',
                       phone    = '$phoneEsc',
                       email    = '$emailEsc'
                 WHERE id = $id AND user_id = $user_id
            ";
            if (!$conn->query($sql)) {
                $response['success'] = false;
                $response['error']   = 'DB: ' . $conn->error;
                return;
            }

            if ($conn->affected_rows === 0) {
                $chk = $conn->query("SELECT id FROM contacts WHERE id = $id AND user_id = $user_id");
                if (!$chk || $chk->num_rows === 0) {
                    $response['success'] = false;
                    $response['error']   = 'Contact inexistent.';
                    return;
                }
            }

            $response['success'] = true;
            $response['message'] = 'Contact actualizat.';
            return;
        }

        // ADD
        $sql = "
            INSERT INTO contacts (user_id, companie, role, name, phone, email)
            VALUES ($user_id, $companie, $role, '$nameEsc', '$phoneEsc', '$emailEsc')
        ";
        if (!$conn->query($sql)) {
            $response['success'] = false;
            $response['error']   = 'DB: ' . $conn->error;
            return;
        }

        $response['success']   = true;
        $response['message']   = 'Contact adăugat.';
        $response['insert_id'] = (int)$conn->insert_id;
        return;
    }

} catch (Throwable $e) {
    $response['success'] = false;
    $response['error']   = 'Eroare la salvare: ' . $e->getMessage();
    return;
}
