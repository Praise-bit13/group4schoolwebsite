<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
require_http_method(['GET', 'POST', 'PUT']);

if ($method === 'GET' && (($_GET['public'] ?? '') === '1')) {
    try {
        $statement = database_connection()->query(
            "SELECT id, title, body, published_at
             FROM announcements
             WHERE status = 'published' AND published_at <= NOW()
             ORDER BY published_at DESC LIMIT 6"
        );
        json_response(200, ['announcements' => $statement->fetchAll()]);
    } catch (PDOException $exception) {
        error_log('School website public announcements query failed: ' . $exception->getMessage());
        json_response(500, ['error' => 'Announcements are temporarily unavailable.']);
    }
}

require_staff_api();

try {
    $pdo = database_connection();
    if ($method === 'GET') {
        $statement = $pdo->query(
            'SELECT a.id, a.title, a.body, a.status, a.published_at, a.created_at,
                    u.full_name AS author
             FROM announcements a
             LEFT JOIN users u ON u.id = a.created_by
             ORDER BY a.created_at DESC LIMIT 100'
        );
        json_response(200, ['announcements' => $statement->fetchAll()]);
    }

    verify_csrf_token();
    $payload = request_payload();
    if ($method === 'POST') {
        $title = required_string($payload, 'title', 'Title', 180);
        $body = required_string($payload, 'body', 'Announcement text', 10000);
        $status = (string) ($payload['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'published'], true)) {
            json_response(422, ['error' => 'Choose draft or published status.']);
        }
        $statement = $pdo->prepare(
            'INSERT INTO announcements (title, body, status, published_at, created_by)
             VALUES (:title, :body, :status,
                     CASE WHEN :published_flag = \'published\' THEN NOW() ELSE NULL END,
                     :created_by)'
        );
        $statement->execute([
            'title' => $title,
            'body' => $body,
            'status' => $status,
            'published_flag' => $status,
            'created_by' => (int) $_SESSION['user_id'],
        ]);
        json_response(201, ['message' => 'Announcement saved.']);
    }

    $id = positive_id($payload['id'] ?? null, 'Announcement');
    $status = (string) ($payload['status'] ?? '');
    if (!in_array($status, ['draft', 'published', 'archived'], true)) {
        json_response(422, ['error' => 'Choose draft, published, or archived status.']);
    }
    $statement = $pdo->prepare(
        "UPDATE announcements SET status = :status,
         published_at = CASE
           WHEN :publish_flag = 'published' AND published_at IS NULL THEN NOW()
           WHEN :clear_flag = 'draft' THEN NULL
           ELSE published_at
         END
         WHERE id = :id"
    );
    $statement->execute([
        'status' => $status,
        'publish_flag' => $status,
        'clear_flag' => $status,
        'id' => $id,
    ]);
    if ($statement->rowCount() === 0) {
        $exists = $pdo->prepare('SELECT 1 FROM announcements WHERE id = :id');
        $exists->execute(['id' => $id]);
        if (!$exists->fetchColumn()) {
            json_response(404, ['error' => 'Announcement not found.']);
        }
    }
    json_response(200, ['message' => 'Announcement status updated.']);
} catch (PDOException $exception) {
    error_log('School website announcement operation failed: ' . $exception->getMessage());
    json_response(500, ['error' => 'The announcement could not be saved. Check the server logs.']);
}
