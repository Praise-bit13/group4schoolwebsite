<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/auth.php';

function json_response(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function require_http_method(array $allowedMethods): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, $allowedMethods, true)) {
        header('Allow: ' . implode(', ', $allowedMethods));
        json_response(405, ['error' => 'This request method is not supported.']);
    }
}

function request_payload(): array
{
    $contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
    if (str_contains($contentType, 'application/json')) {
        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody === false ? '' : $rawBody, true);
        if (!is_array($payload)) {
            json_response(400, ['error' => 'The request body must contain valid JSON.']);
        }

        return $payload;
    }

    return $_POST;
}

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function required_string(array $payload, string $key, string $label, int $maxLength): string
{
    $rawValue = $payload[$key] ?? null;
    if (!is_string($rawValue)) {
        json_response(422, ['error' => $label . ' is required.']);
    }

    $value = trim($rawValue);
    if ($value === '' || strlen($value) > $maxLength) {
        json_response(422, ['error' => $label . ' is required and must be at most ' . $maxLength . ' characters.']);
    }

    return $value;
}

function optional_string(array $payload, string $key, string $label, int $maxLength): ?string
{
    $rawValue = $payload[$key] ?? null;
    if ($rawValue === null) {
        return null;
    }
    if (!is_string($rawValue)) {
        json_response(422, ['error' => $label . ' must be text.']);
    }

    $value = trim($rawValue);
    if ($value === '') {
        return null;
    }
    if (strlen($value) > $maxLength) {
        json_response(422, ['error' => $label . ' must be at most ' . $maxLength . ' characters.']);
    }

    return $value;
}

function optional_email(array $payload): ?string
{
    $email = optional_string($payload, 'email', 'Email', 190);
    if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        json_response(422, ['error' => 'Enter a valid email address.']);
    }

    return $email;
}

function optional_date(array $payload, string $key, string $label): ?string
{
    $value = optional_string($payload, $key, $label, 10);
    if ($value === null) {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if ($date === false || $date->format('Y-m-d') !== $value) {
        json_response(422, ['error' => $label . ' must use YYYY-MM-DD format.']);
    }

    return $value;
}

function positive_id(mixed $value, string $label): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        json_response(422, ['error' => $label . ' must be selected.']);
    }

    return $id;
}

function handle_duplicate(PDOException $exception, string $message): void
{
    if ($exception->getCode() === '23000') {
        json_response(409, ['error' => $message]);
    }

    error_log('School website database operation failed: ' . $exception->getMessage());
    json_response(500, ['error' => 'The request could not be completed. Check the server logs or contact the administrator.']);
}