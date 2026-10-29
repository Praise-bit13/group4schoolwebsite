<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: admin/index.php');
    exit;
}

$error = '';
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $emailValue = $_POST['email'] ?? '';
    $passwordValue = $_POST['password'] ?? '';
    $tokenValue = $_POST['csrf_token'] ?? '';
    $email = is_string($emailValue) ? strtolower(trim($emailValue)) : '';
    $password = is_string($passwordValue) ? $passwordValue : '';
    $providedToken = is_string($tokenValue) ? $tokenValue : '';

    if (!hash_equals(csrf_token(), $providedToken)) {
        $error = 'Your sign-in form expired. Refresh this page and try again.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false || $password === '') {
        $error = 'Enter a valid email address and password.';
    } else {
        try {
            $statement = database_connection()->prepare(
                'SELECT id, full_name, email, password_hash, role
                 FROM users WHERE email = :email AND is_active = 1 LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();
            if ($user === false || !password_verify($password, $user['password_hash'])) {
                $error = 'Email or password was not recognized.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                unset($_SESSION['csrf_token']);
                header('Location: admin/index.php');
                exit;
            }
        } catch (PDOException $exception) {
            error_log('School website sign-in failed: ' . $exception->getMessage());
            $error = 'The sign-in service could not connect to the database. Please try again later.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff sign-in | Federal Polytechnic Kampe</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="admin/admin.css">
  </head>
  <body class="admin-body login-body">
    <main class="login-card">
      <a class="brand" href="index.html">
        <span class="brand-mark" aria-hidden="true">FPK</span>
        <span class="brand-name">
          <strong>Federal Polytechnic Kampe</strong>
          <small>Staff portal</small>
        </span>
      </a>
      <p class="eyebrow eyebrow-green">Authorized staff</p>
      <h1>Sign in</h1>
      <p class="login-intro">Use your school staff account to manage student records and school updates.</p>
      <?php if ($error !== ''): ?>
        <p class="form-message is-error" role="alert"><?= escape_html($error) ?></p>
      <?php endif; ?>
      <form method="post" action="login.php" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>">
        <label for="email">Staff email</label>
        <input id="email" name="email" type="email" autocomplete="username" maxlength="190" required value="<?= escape_html($email) ?>">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <button class="button button-gold" type="submit">Sign in <span aria-hidden="true">→</span></button>
      </form>
      <a class="login-back" href="index.html">← Back to the school website</a>
    </main>
  </body>
</html>