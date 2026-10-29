<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

require_http_method(['POST']);
$payload = request_payload();
$referenceValue = $payload['reference_code'] ?? null;
if (!is_string($referenceValue)) {
    json_response(422, ['error' => 'Enter your application reference.']);
}
$reference = strtoupper(trim($referenceValue));
if (!preg_match('/^FPK-[A-F0-9]{12}$/', $reference)) {
    json_response(422, ['error' => 'Enter the reference code shown after your application was submitted.']);
}
$email = optional_email($payload);
if ($email === null) {
    json_response(422, ['error' => 'Enter the email address used for the application.']);
}

try {
    $statement = database_connection()->prepare(
        'SELECT a.reference_code, a.status, a.created_at, a.reviewed_at,
                d.name AS department_name, p.name AS programme_name
         FROM applications a
         JOIN departments d ON d.id = a.department_id
         JOIN programmes p ON p.id = a.programme_id
         WHERE a.reference_code = :reference_code AND a.email = :email LIMIT 1'
    );
    $statement->execute([
        'reference_code' => $reference,
        'email' => strtolower($email),
    ]);
    $application = $statement->fetch();
    if ($application === false) {
        json_response(404, ['error' => 'No application matched that reference and email address.']);
    }
    json_response(200, ['application' => $application]);
} catch (PDOException $exception) {
    error_log('School website application status lookup failed: ' . $exception->getMessage());
    json_response(500, ['error' => 'Application status is temporarily unavailable. Please try again later.']);
}