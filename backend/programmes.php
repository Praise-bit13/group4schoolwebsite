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
        $departmentId = $_GET['department_id'] ?? null;
        if ($departmentId !== null && $departmentId !== '') {
            $statement = $pdo->prepare(
                'SELECT id, department_id, name, code, award, duration_years, is_active
                 FROM programmes WHERE department_id = :department_id ORDER BY name'
            );
            $statement->execute(['department_id' => positive_id($departmentId, 'Department')]);
        } else {
            $statement = $pdo->query(
                'SELECT id, department_id, name, code, award, duration_years, is_active
                 FROM programmes ORDER BY name'
            );
        }
        json_response(200, ['programmes' => $statement->fetchAll()]);
    }

    verify_csrf_token();
    $payload = request_payload();
    $departmentId = positive_id($payload['department_id'] ?? null, 'Department');
    $name = required_string($payload, 'name', 'Programme name', 160);
    $code = strtoupper(required_string($payload, 'code', 'Programme code', 30));
    if (!preg_match('/^[A-Z0-9-]+$/', $code)) {
        json_response(422, ['error' => 'Programme code may contain only letters, numbers, and hyphens.']);
    }
    $award = optional_string($payload, 'award', 'Award', 80);
    $duration = $payload['duration_years'] ?? '';
    if ($duration === '' || $duration === null) {
        $duration = null;
    } else {
        $duration = filter_var($duration, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
        if ($duration === false) {
            json_response(422, ['error' => 'Duration must be between 1 and 12 years.']);
        }
    }

    if ($method === 'POST') {
        $statement = $pdo->prepare(
            'INSERT INTO programmes (department_id, name, code, award, duration_years)
             VALUES (:department_id, :name, :code, :award, :duration_years)'
        );
        $statement->execute([
            'department_id' => $departmentId,
            'name' => $name,
            'code' => $code,
            'award' => $award,
            'duration_years' => $duration,
        ]);
        json_response(201, ['message' => 'Programme created.']);
    }

    $id = positive_id($payload['id'] ?? null, 'Programme');
    $statement = $pdo->prepare(
        'UPDATE programmes SET department_id = :department_id, name = :name, code = :code,
         award = :award, duration_years = :duration_years WHERE id = :id'
    );
    $statement->execute([
        'department_id' => $departmentId,
        'name' => $name,
        'code' => $code,
        'award' => $award,
        'duration_years' => $duration,
        'id' => $id,
    ]);
    if ($statement->rowCount() === 0) {
        $exists = $pdo->prepare('SELECT 1 FROM programmes WHERE id = :id');
        $exists->execute(['id' => $id]);
        if (!$exists->fetchColumn()) {
            json_response(404, ['error' => 'Programme not found.']);
        }
    }
    json_response(200, ['message' => 'Programme updated.']);
} catch (PDOException $exception) {
    handle_duplicate($exception, 'Programme code is already in use or the selected department does not exist.');
}
