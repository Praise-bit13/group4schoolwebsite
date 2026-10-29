<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

require_http_method(['GET']);

try {
    $pdo = database_connection();

    $departmentStatement = $pdo->query(
        'SELECT d.id, d.name, d.code, d.description
         FROM departments d
         WHERE d.is_active = 1
           AND d.code NOT LIKE \'DEMO-%\' AND d.name NOT LIKE \'Sample - %\'
         ORDER BY d.name'
    );
    $departments = $departmentStatement->fetchAll();

    $programmeSql = 'SELECT p.id, p.department_id, p.name, p.code, p.award, p.duration_years
                     FROM programmes p
                     JOIN departments d ON d.id = p.department_id
                     WHERE p.is_active = 1 AND d.is_active = 1
                       AND p.code NOT LIKE \'DEMO-%\' AND p.name NOT LIKE \'Sample - %\'
                       AND d.code NOT LIKE \'DEMO-%\' AND d.name NOT LIKE \'Sample - %\'
                     ORDER BY d.name, p.name';
    $programmes = $pdo->query($programmeSql)->fetchAll();

    $courseSql = 'SELECT c.id, c.department_id, c.code, c.title, c.credit_units, c.is_suggested
                  FROM courses c
                  JOIN departments d ON d.id = c.department_id
                  WHERE c.is_active = 1 AND d.is_active = 1
                    AND c.code NOT LIKE \'DEMO-%\' AND c.title NOT LIKE \'Sample - %\'
                    AND d.code NOT LIKE \'DEMO-%\' AND d.name NOT LIKE \'Sample - %\'
                  ORDER BY d.name, c.code';
    $courses = $pdo->query($courseSql)->fetchAll();

    $sessionStatement = $pdo->query(
        'SELECT name, starts_on, ends_on
         FROM academic_sessions WHERE is_current = 1 ORDER BY id DESC LIMIT 1'
    );
    $currentSession = $sessionStatement->fetch() ?: null;

    json_response(200, [
        'current_session' => $currentSession,
        'departments' => $departments,
        'programmes' => $programmes,
        'courses' => $courses,
    ]);
} catch (PDOException $exception) {
    error_log('School website public catalogue query failed: ' . $exception->getMessage());
    json_response(500, ['error' => 'The course catalogue is temporarily unavailable. Please contact the school office.']);
}
