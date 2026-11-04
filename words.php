<?php
require 'config.php';
require_login();
$userId = current_user_id();
$today  = date('Y-m-d');

// Assign today's words the first time this user visits today
$stmt = $mysqli->prepare('SELECT COUNT(*) c FROM user_daily_words WHERE user_id = ? AND study_date = ?');
$stmt->bind_param('is', $userId, $today);
$stmt->execute();
$already = (int) $stmt->get_result()->fetch_assoc()['c'];

if ($already === 0) {
    $sql = "SELECT id FROM words WHERE id NOT IN
            (SELECT word_id FROM user_daily_words WHERE user_id = ?)
            ORDER BY id ASC LIMIT " . WORDS_PER_DAY;
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $newIds = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id');

    if ($newIds) {
        $insert = $mysqli->prepare(
            'INSERT INTO user_daily_words (user_id, word_id, study_date, is_learned) VALUES (?, ?, ?, 0)'
        );
        foreach ($newIds as $wordId) {
            $insert->bind_param('iis', $userId, $wordId, $today);
            $insert->execute();
        }
    }
}

// Handle "learned" toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['daily_id'])) {
    $dailyId = (int) $_POST['daily_id'];
    $learned = (int) $_POST['learned'];
    $stmt = $mysqli->prepare('UPDATE user_daily_words SET is_learned = ? WHERE id = ? AND user_id = ?');
    $stmt->bind_param('iii', $learned, $dailyId, $userId);
    $stmt->execute();
    header('Location: words.php');
    exit;
}

// Fetch today's words
$stmt = $mysqli->prepare(
    'SELECT w.*, d.id AS daily_id, d.is_learned
     FROM user_daily_words d JOIN words w ON w.id = d.word_id
     WHERE d.user_id = ? AND d.study_date = ? ORDER BY d.id ASC'
);
$stmt->bind_param('is', $userId, $today);
$stmt->execute();
$words = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Overall stats
$stmt = $mysqli->prepare(
    'SELECT COUNT(*) total, SUM(is_learned) learned, COUNT(DISTINCT study_date) days
     FROM user_daily_words WHERE user_id = ?'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$learnedToday = 0;
foreach ($words as $w) { if ($w['is_learned']) $learnedToday++; }
$count = count($words);
$pct = $count ? round($learnedToday / $count * 100) : 0;

$pageTitle = "Today's Words";
require 'includes/header.php';
?>
<div class="card">
  <h2 style="margin-top:0">Today · <?= e(date('F j, Y')) ?></h2>
  <div class="bar"><div style="width:<?= $pct ?>%"></div></div>
  <p><?= $learnedToday ?>/<?= $count ?> learned today ·
     <?= (int) $stats['learned'] ?> total learned · <?= (int) $stats['days'] ?> study day(s)</p>
</div>

<?php if (empty($words)): ?>
  <div class="card">🎉 You've studied every word in the database. Add more rows to the <code>words</code> table.</div>
<?php endif; ?>

<?php foreach ($words as $w): ?>
  <div class="card <?= $w['is_learned'] ? 'done' : '' ?>">
    <div><span class="ko"><?= e($w['korean']) ?></span><span class="rom"><?= e($w['romanization']) ?></span></div>
    <div class="mean"><?= e($w['meaning']) ?></div>
    <?php if ($w['example_ko']): ?>
      <div class="ex"><?= e($w['example_ko']) ?><br><?= e($w['example_en']) ?></div>
    <?php endif; ?>
    <form method="post" style="margin-top:10px">
      <input type="hidden" name="daily_id" value="<?= (int) $w['daily_id'] ?>">
      <input type="hidden" name="learned" value="<?= $w['is_learned'] ? 0 : 1 ?>">
      <button type="submit" class="<?= $w['is_learned'] ? 'alt' : '' ?>">
        <?= $w['is_learned'] ? 'Mark as not learned' : '✓ I learned this' ?>
      </button>
    </form>
  </div>
<?php endforeach; ?>
<?php require 'includes/footer.php'; ?>
