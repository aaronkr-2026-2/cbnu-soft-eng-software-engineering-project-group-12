<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Daily Korean') ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<nav>
  <strong>🇰🇷 Daily Korean</strong>
  <?php if (current_user_id()): ?>
    <a href="words.php">Today</a>
    <a href="history.php">History</a>
    <a href="my_words.php">My Words</a>
    <span class="sp"></span>
    <span><?= e($_SESSION['user_name'] ?? '') ?></span>
    <a href="logout.php">Logout</a>
  <?php else: ?>
    <span class="sp"></span>
    <a href="login.php">Login</a>
    <a href="register.php">Register</a>
  <?php endif; ?>
</nav>
<div class="wrap">
<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="msg err"><?= e($_SESSION['flash_error']) ?></div>
  <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="msg ok"><?= e($_SESSION['flash_success']) ?></div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
