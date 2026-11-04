<?php
require 'config.php';
if (current_user_id()) { header('Location: words.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->fetch_row()) {
            $errors[] = 'That email is already registered.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare('INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->bind_param('sss', $name, $email, $hash);
        $stmt->execute();
        $_SESSION['flash_success'] = 'Account created. Please log in.';
        header('Location: login.php');
        exit;
    }
}

$pageTitle = 'Register';
require 'includes/header.php';
?>
<div class="card">
  <h2>Create account</h2>
  <?php foreach ($errors as $err): ?><div class="msg err"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post">
    <label>Name</label>
    <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>">
    <label>Email</label>
    <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>">
    <label>Password (min 6 characters)</label>
    <input type="password" name="password">
    <label>Confirm password</label>
    <input type="password" name="confirm">
    <button type="submit">Register</button>
  </form>
</div>
<?php require 'includes/footer.php'; ?>
