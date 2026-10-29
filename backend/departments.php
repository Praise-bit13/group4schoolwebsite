<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

require_staff_api();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
require_http_method(['GET', 'POST', 'PUT']);

try {
    $pdo = database_connection();
    if ($method === 'GET') {
        $statement = $pdo->query('SELECT id, name, code, description, is_active FROM departments ORDER BY name');
        json_response(200, ['departments' => $statement->fetchAll()]);
    }

    verify_csrf_token();
    $payload = request_payload();
    $name = required_string($payload, 'name', 'Department name', 120);
    $code = strtoupper(required_string($payload, 'code', 'Department code', 20));
    if (!preg_match('/^[A-Z0-9-]+$/', $code)) {
        json_response(422, ['error' => 'Department code may contain only letters, numbers, and hyphens.']);
    }
    $description = optional_string($payload, 'description', 'Description', 5000);

    if ($method === 'POST') {
        $statement = $pdo->prepare(
            'INSERT INTO departments (name, code, description) VALUES (:name, :code, :description)'
        );
        $statement->execute(['name' => $name, 'code' => $code, 'description' => $description]);
        json_response(201, ['message' => 'Department created.']);
    }

    $id = positive_id($payload['id'] ?? null, 'Department');
    $statement = $pdo->prepare(
        'UPDATE departments SET name = :name, code = :code, description = :description WHERE id = :id'
    );
    $statement->execute(['name' => $name, 'code' => $code, 'description' => $description, 'id' => $id]);
    if ($statement->rowCount() === 0) {
        $exists = $pdo->prepare('SELECT 1 FROM departments WHERE id = :id');
        $exists->execute(['id' => $id]);
        if (!$exists->fetchColumn()) {
            json_response(404, ['error' => 'Department not found.']);
        }
    }
    json_response(200, ['message' => 'Department updated.']);
} catch (PDOException $exception) {
    handle_duplicate($exception, 'Department name or code is already in use.');
}
