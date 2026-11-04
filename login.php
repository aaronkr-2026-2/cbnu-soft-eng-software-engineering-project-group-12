<?php
require 'config.php';
if (current_user_id()) { header('Location: words.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $mysqli->prepare('SELECT id, name, password FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header('Location: words.php');
        exit;
    }
    $errors[] = 'Wrong email or password.';
}

$pageTitle = 'Login';
require 'includes/header.php';
?>
<div class="card">
  <h2>Login</h2>
  <?php foreach ($errors as $err): ?><div class="msg err"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post">
    <label>Email</label>
    <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>">
    <label>Password</label>
    <input type="password" name="password">
    <button type="submit">Login</button>
  </form>
</div>
<?php require 'includes/footer.php'; ?>
