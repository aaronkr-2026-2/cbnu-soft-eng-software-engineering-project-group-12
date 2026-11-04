<?php
require 'config.php';
require_login();
$userId = current_user_id();

$stmt = $mysqli->prepare(
    'SELECT w.korean, w.romanization, w.meaning, d.study_date, d.is_learned
     FROM user_daily_words d JOIN words w ON w.id = d.word_id
     WHERE d.user_id = ? ORDER BY d.study_date DESC, d.id ASC'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'History';
require 'includes/header.php';
?>
<div class="card">
  <h2 style="margin-top:0">History</h2>
  <?php if (empty($rows)): ?>
    <p>No words yet.</p>
  <?php else: ?>
  <table>
    <tr><th>Date</th><th>Korean</th><th>Romanization</th><th>Meaning</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['study_date']) ?></td>
        <td><?= e($r['korean']) ?></td>
        <td><?= e($r['romanization']) ?></td>
        <td><?= e($r['meaning']) ?></td>
        <td><?= $r['is_learned'] ? '✅' : '' ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<?php require 'includes/footer.php'; ?>
