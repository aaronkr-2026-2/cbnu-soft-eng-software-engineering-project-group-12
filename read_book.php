<?php
require 'config.php';
require_login();
require 'includes/files.php';

$id   = (int) ($_GET['id'] ?? 0);
$book = find_user_file($mysqli, $id, current_user_id(), 'pdf');
if (!$book) {
    $_SESSION['flash_error'] = 'That book could not be found. It may have been deleted.';
    header('Location: library.php?tab=books');
    exit;
}

$src = 'file.php?id=' . (int) $book['id'];
$pageTitle = $book['original_name'];
require 'includes/header.php';
?>
<style>
  .wrap{max-width:1000px}
  .reader-head{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px}
  .reader-head h2{margin:0;flex:1;min-width:200px;font-size:1.1rem;word-break:break-word}
  .reader-frame{width:100%;height:78vh;border:1px solid #d1d5db;border-radius:12px;background:#fff}
  .reader-note{color:#666;font-size:.9rem;margin-top:8px}
</style>
<div class="reader-head">
  <h2>📕 <?= e($book['original_name']) ?></h2>
  <a class="btn alt" href="library.php?tab=books">← Back to library</a>
  <a class="btn alt" href="<?= e($src) ?>" target="_blank" rel="noopener">Open full screen</a>
  <a class="btn alt" href="<?= e($src) ?>&amp;download=1">Download</a>
</div>
<iframe class="reader-frame" src="<?= e($src) ?>" title="<?= e($book['original_name']) ?>"></iframe>
<p class="reader-note">Can't see the book? Your browser may not show PDFs inside a page — use “Open full screen” or “Download”.</p>
<?php require 'includes/footer.php'; ?>
