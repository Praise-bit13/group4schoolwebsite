<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
require_http_method(['GET', 'POST', 'PUT']);

if ($method === 'POST') {
    verify_csrf_token();
    $payload = request_payload();
    if (optional_string($payload, 'website', 'Website', 200) !== null) {
        json_response(422, ['error' => 'The application could not be submitted. Please review the form and try again.']);
    }
    if (($payload['consent'] ?? '') !== 'yes') {
        json_response(422, ['error' => 'Confirm that the school may use these details to process your application.']);
    }

    $firstName = required_string($payload, 'first_name', 'First name', 80);
    $lastName = required_string($payload, 'last_name', 'Last name', 80);
    $email = optional_email($payload);
    if ($email === null) {
        json_response(422, ['error' => 'Email is required.']);
    }
    $phone = required_string($payload, 'phone', 'Phone number', 30);
    $studyMode = (string) ($payload['study_mode'] ?? '');
    if (!in_array($studyMode, ['full_time', 'part_time'], true)) {
        json_response(422, ['error' => 'Choose full-time or part-time study mode.']);
    }
    $departmentId = positive_id($payload['department_id'] ?? null, 'Department');
    $programmeId = positive_id($payload['programme_id'] ?? null, 'Programme');
    $qualification = optional_string($payload, 'previous_qualification', 'Previous qualification', 160);

    try {
        $pdo = database_connection();
        $programmeStatement = $pdo->prepare(
            "SELECT p.id
             FROM programmes p
             JOIN departments d ON d.id = p.department_id
             WHERE p.id = :programme_id AND p.department_id = :department_id
               AND p.is_active = 1 AND d.is_active = 1
               AND p.code NOT LIKE 'DEMO-%' AND p.name NOT LIKE 'Sample - %'
               AND d.code NOT LIKE 'DEMO-%' AND d.name NOT LIKE 'Sample - %'"
        );
        $programmeStatement->execute([
            'programme_id' => $programmeId,
            'department_id' => $departmentId,
        ]);
        if (!$programmeStatement->fetchColumn()) {
            json_response(422, ['error' => 'Choose an active programme from the selected department.']);
        }

        $reference = 'FPK-' . strtoupper(bin2hex(random_bytes(6)));
        $statement = $pdo->prepare(
            'INSERT INTO applications
              (reference_code, first_name, last_name, email, phone, study_mode,
               department_id, programme_id, previous_qualification)
             VALUES
              (:reference_code, :first_name, :last_name, :email, :phone, :study_mode,
               :department_id, :programme_id, :previous_qualification)'
        );
        $statement->execute([
            'reference_code' => $reference,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower($email),
            'phone' => $phone,
            'study_mode' => $studyMode,
            'department_id' => $departmentId,
            'programme_id' => $programmeId,
            'previous_qualification' => $qualification,
        ]);
        json_response(201, [
            'message' => 'Your application has been received.',
            'reference_code' => $reference,
        ]);
    } catch (PDOException $exception) {
        error_log('School website application submission failed: ' . $exception->getMessage());
        json_response(500, ['error' => 'The application could not be submitted. Please try again later.']);
    }
}

require_staff_api();
if ($method === 'GET') {
    $searchValue = $_GET['q'] ?? '';
    $statusValue = $_GET['status'] ?? '';
    if (!is_string($searchValue) || !is_string($statusValue)) {
        json_response(422, ['error' => 'Search and status filters must be text.']);
    }
    $search = trim($searchValue);
    $status = $statusValue;
    if ($status !== '' && !in_array($status, ['submitted', 'reviewing', 'accepted', 'declined'], true)) {
        json_response(422, ['error' => 'Choose a valid application status.']);
    }

    try {
        $where = [];
        $parameters = [];
        if ($search !== '') {
            $where[] = '(a.reference_code LIKE :reference OR a.first_name LIKE :first_name
                         OR a.last_name LIKE :last_name OR a.email LIKE :email)';
            $term = '%' . $search . '%';
            $parameters['reference'] = $term;
            $parameters['first_name'] = $term;
            $parameters['last_name'] = $term;
            $parameters['email'] = $term;
        }
        if ($status !== '') {
            $where[] = 'a.status = :status';
            $parameters['status'] = $status;
        }
        $whereClause = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $statement = database_connection()->prepare(
            'SELECT a.id, a.reference_code, a.first_name, a.last_name, a.email, a.phone,
                    a.study_mode, a.previous_qualification, a.status, a.created_at, a.reviewed_at,
                    d.name AS department_name, p.name AS programme_name
             FROM applications a
             JOIN departments d ON d.id = a.department_id
             JOIN programmes p ON p.id = a.programme_id
             ' . $whereClause . '
             ORDER BY a.created_at DESC LIMIT 200'
        );
        $statement->execute($parameters);
        json_response(200, ['applications' => $statement->fetchAll()]);
    } catch (PDOException $exception) {
        error_log('School website application list failed: ' . $exception->getMessage());
        json_response(500, ['error' => 'Applications could not be loaded. Check the server logs.']);
    }
}

verify_csrf_token();
$payload = request_payload();
$id = positive_id($payload['id'] ?? null, 'Application');
$status = (string) ($payload['status'] ?? '');
if (!in_array($status, ['submitted', 'reviewing', 'accepted', 'declined'], true)) {
    json_response(422, ['error' => 'Choose submitted, reviewing, accepted, or declined status.']);
}

try {
    $pdo = database_connection();
    $statement = $pdo->prepare(
        'UPDATE applications SET status = :status,
         reviewed_by = :reviewed_by, reviewed_at = NOW() WHERE id = :id'
    );
    $statement->execute([
        'status' => $status,
        'reviewed_by' => (int) $_SESSION['user_id'],
        'id' => $id,
    ]);
    if ($statement->rowCount() === 0) {
        $exists = $pdo->prepare('SELECT 1 FROM applications WHERE id = :id');
        $exists->execute(['id' => $id]);
        if (!$exists->fetchColumn()) {
            json_response(404, ['error' => 'Application not found.']);
        }
    }
    json_response(200, ['message' => 'Application status updated.']);
} catch (PDOException $exception) {
    error_log('School website application review failed: ' . $exception->getMessage());
    json_response(500, ['error' => 'Application status could not be updated. Check the server logs.']);
}