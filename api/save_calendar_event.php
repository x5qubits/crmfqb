<?php
$id = (int)($_POST['id'] ?? 0);
$data = [
    'user_id' => $user_id,
    'type' => $_POST['type'] ?? 'todo',
    'title' => trim($_POST['title'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
    'start' => $_POST['start'] ?? null,
    'end' => $_POST['end'] ?? null,
    'all_day' => isset($_POST['all_day']) ? 1 : 0,
    'email_id' => (int)($_POST['email_id'] ?? 0) ?: null
];

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE calendar_events SET type=?, title=?, description=?, start=?, end=?, all_day=?, email_id=? WHERE id=? AND user_id=?");
        $stmt->execute([$data['type'], $data['title'], $data['description'], $data['start'], $data['end'], $data['all_day'], $data['email_id'], $id, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO calendar_events (user_id, type, title, description, start, end, all_day, email_id) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$data['user_id'], $data['type'], $data['title'], $data['description'], $data['start'], $data['end'], $data['all_day'], $data['email_id']]);
    }
    $response['success'] = true;
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}