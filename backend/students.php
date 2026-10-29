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
        $searchValue = $_GET['q'] ?? '';
        $modeValue = $_GET['study_mode'] ?? '';
        if (!is_string($searchValue) || !is_string($modeValue)) {
            json_response(422, ['error' => 'Search and study mode filters must be text.']);
        }
        $search = trim($searchValue);
        $studyMode = $modeValue;
        if ($studyMode !== '' && !in_array($studyMode, ['full_time', 'part_time'], true)) {
            json_response(422, ['error' => 'Choose full-time or part-time study mode.']);
        }

        $where = [];
        $parameters = [];
        if ($search !== '') {
            $where[] = '(s.admission_number LIKE :search OR s.first_name LIKE :search_name OR s.last_name LIKE :search_last)';
            $term = '%' . $search . '%';
            $parameters['search'] = $term;
            $parameters['search_name'] = $term;
            $parameters['search_last'] = $term;
        }
        if ($studyMode !== '') {
            $where[] = 's.study_mode = :study_mode';
            $parameters['study_mode'] = $studyMode;
        }
        $whereClause = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $sql = 'SELECT s.id, s.admission_number, s.first_name, s.last_name, s.email, s.phone,
                       s.study_mode, s.status, s.department_id, s.programme_id,
                       d.name AS department_name, p.name AS programme_name
                FROM students s
                JOIN departments d ON d.id = s.department_id
                JOIN programmes p ON p.id = s.programme_id
                ' . $whereClause . '
                ORDER BY s.last_name, s.first_name LIMIT 200';
        $statement = $pdo->prepare($sql);
        $statement->execute($parameters);
        json_response(200, ['students' => $statement->fetchAll()]);
    }

    verify_csrf_token();
    $payload = request_payload();
    $admissionNumber = strtoupper(required_string($payload, 'admission_number', 'Admission number', 32));
    if (!preg_match('~^[A-Z0-9/-]+$~', $admissionNumber)) {
        json_response(422, ['error' => 'Admission number may contain only letters, numbers, hyphens, and slashes.']);
    }
    $firstName = required_string($payload, 'first_name', 'First name', 80);
    $lastName = required_string($payload, 'last_name', 'Last name', 80);
    $email = optional_email($payload);
    $phone = optional_string($payload, 'phone', 'Phone number', 30);
    $studyMode = (string) ($payload['study_mode'] ?? '');
    if (!in_array($studyMode, ['full_time', 'part_time'], true)) {
        json_response(422, ['error' => 'Choose full-time or part-time study mode.']);
    }
    $departmentId = positive_id($payload['department_id'] ?? null, 'Department');
    $programmeId = positive_id($payload['programme_id'] ?? null, 'Programme');
    $dateOfBirth = optional_date($payload, 'date_of_birth', 'Date of birth');

    if ($method === 'POST') {
        $statement = $pdo->prepare(
            'INSERT INTO students
              (admission_number, first_name, last_name, email, phone, date_of_birth,
               study_mode, department_id, programme_id, created_by)
             VALUES
              (:admission_number, :first_name, :last_name, :email, :phone, :date_of_birth,
               :study_mode, :department_id, :programme_id, :created_by)'
        );
        $statement->execute([
            'admission_number' => $admissionNumber,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'date_of_birth' => $dateOfBirth,
            'study_mode' => $studyMode,
            'department_id' => $departmentId,
            'programme_id' => $programmeId,
            'created_by' => (int) $_SESSION['user_id'],
        ]);
        json_response(201, ['message' => 'Student record created.']);
    }

    $id = positive_id($payload['id'] ?? null, 'Student');
    $statement = $pdo->prepare(
        'UPDATE students SET admission_number = :admission_number, first_name = :first_name,
         last_name = :last_name, email = :email, phone = :phone, date_of_birth = :date_of_birth,
         study_mode = :study_mode, department_id = :department_id, programme_id = :programme_id
         WHERE id = :id'
    );
    $statement->execute([
        'admission_number' => $admissionNumber,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'date_of_birth' => $dateOfBirth,
        'study_mode' => $studyMode,
        'department_id' => $departmentId,
        'programme_id' => $programmeId,
        'id' => $id,
    ]);
    if ($statement->rowCount() === 0) {
        $exists = $pdo->prepare('SELECT 1 FROM students WHERE id = :id');
        $exists->execute(['id' => $id]);
        if (!$exists->fetchColumn()) {
            json_response(404, ['error' => 'Student record not found.']);
        }
    }
    json_response(200, ['message' => 'Student record updated.']);
} catch (PDOException $exception) {
    handle_duplicate($exception, 'Admission number is already registered, or the selected department and programme do not match.');
}
