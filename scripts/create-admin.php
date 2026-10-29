<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

function prompt(string $label): string
{
    fwrite(STDOUT, $label);
    $value = fgets(STDIN);
    return trim($value === false ? '' : $value);
}

$fullName = prompt('Staff full name: ');
$email = strtolower(prompt('Staff email: '));
$role = strtolower(prompt('Role (admin/staff): '));
if (!in_array($role, ['admin', 'staff'], true)) {
    fwrite(STDERR, "Role must be admin or staff.\n");
    exit(1);
}
if ($fullName === '' || strlen($fullName) > 120 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Enter a valid name and email address.\n");
    exit(1);
}

$password = getenv('SCHOOL_ADMIN_PASSWORD');
if ($password === false) {
    fwrite(STDERR, "Set SCHOOL_ADMIN_PASSWORD from a secure prompt before running this command.\n");
    exit(1);
}
putenv('SCHOOL_ADMIN_PASSWORD');
unset($_ENV['SCHOOL_ADMIN_PASSWORD'], $_SERVER['SCHOOL_ADMIN_PASSWORD']);
if (strlen($password) < 12 || strlen($password) > 255) {
    unset($password);
    fwrite(STDERR, "Password must be between 12 and 255 characters.\n");
    exit(1);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
unset($password);

try {
    $statement = database_connection()->prepare(
        'INSERT INTO users (full_name, email, password_hash, role) VALUES (:full_name, :email, :password_hash, :role)'
    );
    $statement->execute([
        'full_name' => $fullName,
        'email' => $email,
        'password_hash' => $passwordHash,
        'role' => $role,
    ]);
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        fwrite(STDERR, "An account already exists for that email address.\n");
        exit(1);
    }
    error_log('School website account creation failed: ' . $exception->getMessage());
    fwrite(STDERR, "Unable to create the account. Check the database connection and server logs.\n");
    exit(1);
}

fwrite(STDOUT, ucfirst($role) . " account created successfully.\n");