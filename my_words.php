<?php
require 'config.php';
require_login();
$userId = current_user_id();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$errors  = [];
$editing = null;   // row being edited, if any
$form    = ['korean' => '', 'romanization' => '', 'meaning' => '', 'example_ko' => '', 'example_en' => ''];

// ---------- Handle POST (add / update / delete) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        die('Invalid request.');
    }
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $stmt = $mysqli->prepare('DELETE FROM user_words WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $userId);
        $stmt->execute();
        $_SESSION['flash_success'] = 'Word deleted.';
        header('Location: my_words.php');
        exit;
    }

    if ($action === 'add' || $action === 'update') {
        foreach ($form as $k => $_) {
            $form[$k] = trim($_POST[$k] ?? '');
        }
        if ($form['korean'] === '')  $errors[] = 'Korean word is required.';
        if ($form['meaning'] === '') $errors[] = 'Meaning is required.';
        if (mb_strlen($form['korean']) > 100 || mb_strlen($form['romanization']) > 100) {
            $errors[] = 'Korean word and romanization must be 100 characters or fewer.';
        }
        if (mb_strlen($form['meaning']) > 200) $errors[] = 'Meaning must be 200 characters or fewer.';
        if (mb_strlen($form['example_ko']) > 255 || mb_strlen($form['example_en']) > 255) {
            $errors[] = 'Examples must be 255 characters or fewer.';
        }

        // Duplicate check (per user, ignoring the row being edited)
        if (!$errors) {
            $stmt = $mysqli->prepare('SELECT id FROM user_words WHERE user_id = ? AND korean = ? AND id <> ?');
            $stmt->bind_param('isi', $userId, $form['korean'], $id);
            $stmt->execute();
            if ($stmt->get_result()->fetch_row()) {
                $errors[] = 'You already saved that word.';
            }
        }

        if (!$errors) {
            // Empty optional fields are stored as NULL
            $rom = $form['romanization'] !== '' ? $form['romanization'] : null;
            $exk = $form['example_ko']   !== '' ? $form['example_ko']   : null;
            $exe = $form['example_en']   !== '' ? $form['example_en']   : null;

            if ($action === 'add') {
                $stmt = $mysqli->prepare(
                    'INSERT INTO user_words (user_id, korean, romanization, meaning, example_ko, example_en, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())'
                );
                $stmt->bind_param('isssss', $userId, $form['korean'], $rom, $form['meaning'], $exk, $exe);
                $stmt->execute();
                $_SESSION['flash_success'] = 'Word added.';
            } else {
                $stmt = $mysqli->prepare(
                    'UPDATE user_words SET korean = ?, romanization = ?, meaning = ?, example_ko = ?, example_en = ?
                     WHERE id = ? AND user_id = ?'
                );
                $stmt->bind_param('sssssii', $form['korean'], $rom, $form['meaning'], $exk, $exe, $id, $userId);
                $stmt->execute();
                $_SESSION['flash_success'] = 'Word updated.';
            }
            header('Location: my_words.php');
            exit;
        }

        // Validation failed: keep the form filled in
        if ($action === 'update') $editing = ['id' => $id];
    }
}

// ---------- Load a word for editing (?edit=ID) ----------
if (!$editing && isset($_GET['edit'])) {
    $stmt = $mysqli->prepare('SELECT * FROM user_words WHERE id = ? AND user_id = ?');
    $eid = (int) $_GET['edit'];
    $stmt->bind_param('ii', $eid, $userId);
    $stmt->execute();
    $editing = $stmt->get_result()->fetch_assoc();
    if ($editing) {
        foreach ($form as $k => $_) $form[$k] = (string) $editing[$k];
    }
}

// ---------- Fetch this user's list (optional search) ----------
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $mysqli->prepare(
        'SELECT * FROM user_words WHERE user_id = ? AND (korean LIKE ? OR romanization LIKE ? OR meaning LIKE ?)
         ORDER BY created_at DESC, id DESC'
    );
    $stmt->bind_param('isss', $userId, $like, $like, $like);
} else {
    $stmt = $mysqli->prepare('SELECT * FROM user_words WHERE user_id = ? ORDER BY created_at DESC, id DESC');
    $stmt->bind_param('i', $userId);
}
$stmt->execute();
$words = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'My Words';
require 'includes/header.php';
?>
<style>
  .row-actions{display:flex;gap:8px;margin-top:10px}
  .row-actions form{margin:0}
  .grid2{display:grid;grid-template-columns:1fr 1fr;gap:0 12px}
  @media(max-width:560px){.grid2{grid-template-columns:1fr}}
  .review-hidden .mean{visibility:hidden;cursor:pointer}
  .review-hidden .mean.shown{visibility:visible}
  .search{display:flex;gap:8px}
  .search input{margin:0}
</style>

<div class="card">
  <h2 style="margin-top:0"><?= $editing ? 'Edit word' : 'Add a new word' ?></h2>
  <?php foreach ($errors as $err): ?><div class="msg err"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
    <input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>
    <div class="grid2">
      <div><label>Korean *</label>
        <input type="text" name="korean" value="<?= e($form['korean']) ?>"></div>
      <div><label>Romanization</label>
        <input type="text" name="romanization" value="<?= e($form['romanization']) ?>"></div>
    </div>
    <label>Meaning *</label>
    <input type="text" name="meaning" value="<?= e($form['meaning']) ?>">
    <div class="grid2">
      <div><label>Example (Korean)</label>
        <input type="text" name="example_ko" value="<?= e($form['example_ko']) ?>"></div>
      <div><label>Example (English)</label>
        <input type="text" name="example_en" value="<?= e($form['example_en']) ?>"></div>
    </div>
    <button type="submit"><?= $editing ? 'Save changes' : 'Add word' ?></button>
    <?php if ($editing): ?><a class="btn alt" href="my_words.php">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <h2 style="margin-top:0">My vocabulary (<?= count($words) ?>)</h2>
  <form method="get" class="search">
    <input type="text" name="q" placeholder="Search my words..." value="<?= e($q) ?>">
    <button type="submit">Search</button>
    <?php if ($q !== ''): ?><a class="btn alt" href="my_words.php">Clear</a><?php endif; ?>
  </form>
  <?php if ($words): ?>
    <p style="margin-bottom:0">
      <button type="button" class="alt" id="reviewBtn">Review mode: hide meanings</button>
      <small style="color:#666"> Click a hidden meaning to reveal it.</small>
    </p>
  <?php endif; ?>
</div>

<div id="list">
<?php if (empty($words)): ?>
  <div class="card"><?= $q !== '' ? 'No words match your search.' : 'You have not added any words yet. Use the form above to add your first one.' ?></div>
<?php endif; ?>

<?php foreach ($words as $w): ?>
  <div class="card">
    <div><span class="ko"><?= e($w['korean']) ?></span><span class="rom"><?= e($w['romanization']) ?></span></div>
    <div class="mean"><?= e($w['meaning']) ?></div>
    <?php if ($w['example_ko'] || $w['example_en']): ?>
      <div class="ex"><?= e($w['example_ko']) ?><br><?= e($w['example_en']) ?></div>
    <?php endif; ?>
    <div class="row-actions">
      <a class="btn alt" href="my_words.php?edit=<?= (int) $w['id'] ?>">Edit</a>
      <form method="post" onsubmit="return confirm('Delete this word?');">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= (int) $w['id'] ?>">
        <button type="submit" style="background:#b91c1c">Delete</button>
      </form>
    </div>
  </div>
<?php endforeach; ?>
</div>

<script>
  var list = document.getElementById('list');
  var btn  = document.getElementById('reviewBtn');
  if (btn) {
    btn.addEventListener('click', function () {
      var on = list.classList.toggle('review-hidden');
      btn.textContent = on ? 'Review mode: show meanings' : 'Review mode: hide meanings';
      list.querySelectorAll('.mean').forEach(function (m) { m.classList.remove('shown'); });
    });
    list.addEventListener('click', function (ev) {
      if (list.classList.contains('review-hidden') && ev.target.classList.contains('mean')) {
        ev.target.classList.toggle('shown');
      }
    });
  }
</script>
<?php require 'includes/footer.php'; ?>
