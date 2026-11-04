<?php
require 'config.php';
require_login();
require 'includes/files.php';

$userId = current_user_id();
$tab    = (($_GET['tab'] ?? 'books') === 'images') ? 'images' : 'books';
$maxMb  = MAX_UPLOAD_BYTES / 1048576;

// ---------- Handle POST (upload / delete) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $back = 'Location: library.php?tab=' . $tab;

    // If the whole request is bigger than PHP's post_max_size, PHP drops the form data entirely.
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $_SESSION['flash_error'] = "That file is too large. The maximum size is {$maxMb} MB per file.";
        header($back);
        exit;
    }
    if (!csrf_ok()) {
        $_SESSION['flash_error'] = 'Your session expired. Please try again.';
        header($back);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'upload_pdf' || $action === 'upload_image') {
        $kind = $action === 'upload_pdf' ? 'pdf' : 'image';
        $tab  = $kind === 'pdf' ? 'books' : 'images';
        $back = 'Location: library.php?tab=' . $tab;

        $error = save_upload($mysqli, $userId, $kind, $_FILES['file'] ?? []);
        if ($error) {
            $_SESSION['flash_error'] = $error;
        } else {
            $_SESSION['flash_success'] = $kind === 'pdf' ? 'Book uploaded.' : 'Image uploaded.';
        }
        header($back);
        exit;
    }

    if ($action === 'delete') {
        $ok = delete_user_file($mysqli, (int) ($_POST['id'] ?? 0), $userId);
        if ($ok) $_SESSION['flash_success'] = 'File deleted.';
        else     $_SESSION['flash_error']   = 'That file could not be found.';
        header($back);
        exit;
    }

    header($back);
    exit;
}

// ---------- Load data ----------
$counts = ['pdf' => 0, 'image' => 0];
$stmt = $mysqli->prepare('SELECT kind, COUNT(*) c FROM user_files WHERE user_id = ? GROUP BY kind');
$stmt->bind_param('i', $userId);
$stmt->execute();
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
    $counts[$r['kind']] = (int) $r['c'];
}

$kind = $tab === 'books' ? 'pdf' : 'image';
$stmt = $mysqli->prepare('SELECT * FROM user_files WHERE user_id = ? AND kind = ? ORDER BY created_at DESC, id DESC');
$stmt->bind_param('is', $userId, $kind);
$stmt->execute();
$files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$csrf = csrf_token();
$pageTitle = 'Library';
require 'includes/header.php';
?>
<style>
  .tabs{display:flex;gap:8px;margin-bottom:14px}
  .tab{padding:9px 16px;border-radius:10px;background:#e5e7eb;color:#374151;text-decoration:none;font-weight:600}
  .tab.active{background:#1e3a8a;color:#fff}
  .tab small{opacity:.75;font-weight:400;margin-left:4px}
  .hint{color:#666;font-size:.9rem;margin:2px 0 10px}
  .upload-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
  .upload-row input[type=file]{flex:1;min-width:200px}
  .js-error{display:none;margin-top:12px;margin-bottom:0}
  .item{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
  .item .icon{font-size:1.8rem}
  .item .meta{flex:1;min-width:180px}
  .item .name{font-weight:600;word-break:break-word}
  .item .sub{color:#666;font-size:.85rem}
  .actions{display:flex;gap:8px;flex-wrap:wrap}
  .actions form{margin:0}
  button.danger{background:#b91c1c}
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
  .tile{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:hidden;display:flex;flex-direction:column}
  .tile .thumb{display:block;width:100%;height:160px;object-fit:cover;background:#e5e7eb;cursor:zoom-in}
  .tile .body{padding:10px 12px}
  .tile .actions{margin-top:8px}
  .tile .actions .btn,.tile .actions button{padding:6px 10px;font-size:.85rem}
  .empty{text-align:center;color:#666}
  #lightbox{display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:50;align-items:center;justify-content:center;flex-direction:column;padding:20px}
  #lightbox.open{display:flex}
  #lightbox img{max-width:100%;max-height:85vh;border-radius:8px}
  #lightbox p{color:#fff;margin:10px 0 0}
</style>

<div class="tabs">
  <a class="tab <?= $tab === 'books'  ? 'active' : '' ?>" href="library.php?tab=books">📕 PDF Books<small><?= $counts['pdf'] ?></small></a>
  <a class="tab <?= $tab === 'images' ? 'active' : '' ?>" href="library.php?tab=images">🖼️ Images<small><?= $counts['image'] ?></small></a>
</div>

<div class="card">
  <h2 style="margin-top:0"><?= $tab === 'books' ? 'Upload a PDF book' : 'Upload an image' ?></h2>
  <p class="hint">
    <?= $tab === 'books' ? 'PDF files only' : 'JPG, PNG, GIF or WebP' ?> · up to <?= $maxMb ?> MB per file
  </p>
  <form method="post" action="library.php?tab=<?= e($tab) ?>" enctype="multipart/form-data" id="upload-form"
        data-max="<?= (int) MAX_UPLOAD_BYTES ?>" data-kind="<?= $tab === 'books' ? 'pdf' : 'image' ?>">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="action" value="<?= $tab === 'books' ? 'upload_pdf' : 'upload_image' ?>">
    <div class="upload-row">
      <input type="file" name="file" id="file-input"
             accept="<?= $tab === 'books' ? '.pdf,application/pdf' : '.jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp' ?>">
      <button type="submit" id="upload-btn">Upload</button>
    </div>
    <div class="msg err js-error" id="js-error" role="alert"></div>
  </form>
</div>

<?php if (empty($files)): ?>
  <div class="card empty">
    <?= $tab === 'books'
        ? 'You haven’t uploaded any books yet. Choose a PDF above to get started.'
        : 'You haven’t uploaded any images yet. Choose an image above to get started.' ?>
  </div>

<?php elseif ($tab === 'books'): ?>
  <?php foreach ($files as $f): ?>
    <div class="card item">
      <div class="icon">📕</div>
      <div class="meta">
        <div class="name"><?= e($f['original_name']) ?></div>
        <div class="sub"><?= e(format_bytes((int) $f['size_bytes'])) ?> · uploaded <?= e(date('M j, Y', strtotime($f['created_at']))) ?></div>
      </div>
      <div class="actions">
        <a class="btn" href="read_book.php?id=<?= (int) $f['id'] ?>">Read</a>
        <a class="btn alt" href="file.php?id=<?= (int) $f['id'] ?>&amp;download=1">Download</a>
        <form method="post" action="library.php?tab=books" class="js-delete" data-name="<?= e($f['original_name']) ?>">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
          <button type="submit" class="danger">Delete</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>

<?php else: ?>
  <div class="grid">
    <?php foreach ($files as $f): ?>
      <div class="tile">
        <img class="thumb" loading="lazy" src="file.php?id=<?= (int) $f['id'] ?>" alt="<?= e($f['original_name']) ?>"
             data-full="file.php?id=<?= (int) $f['id'] ?>" data-caption="<?= e($f['original_name']) ?>">
        <div class="body">
          <div class="name" style="font-weight:600;word-break:break-word"><?= e($f['original_name']) ?></div>
          <div class="sub" style="color:#666;font-size:.85rem"><?= e(format_bytes((int) $f['size_bytes'])) ?> · <?= e(date('M j, Y', strtotime($f['created_at']))) ?></div>
          <div class="actions">
            <a class="btn alt" href="file.php?id=<?= (int) $f['id'] ?>&amp;download=1">Download</a>
            <form method="post" action="library.php?tab=images" class="js-delete" data-name="<?= e($f['original_name']) ?>">
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
              <button type="submit" class="danger">Delete</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div id="lightbox" role="dialog" aria-label="Image viewer"><img alt=""><p></p></div>
<?php endif; ?>

<script>
(function () {
  // ---- Check the file before it is uploaded ----
  var form = document.getElementById('upload-form');
  var input = document.getElementById('file-input');
  var errBox = document.getElementById('js-error');
  var btn = document.getElementById('upload-btn');
  var max = parseInt(form.dataset.max, 10);
  var kind = form.dataset.kind;
  var imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

  function showError(msg) { errBox.textContent = msg; errBox.style.display = 'block'; }
  function clearError() { errBox.style.display = 'none'; errBox.textContent = ''; }
  function mb(n) { return (n / 1048576).toFixed(1) + ' MB'; }

  function check(file) {
    var ext = file.name.split('.').pop().toLowerCase();
    if (kind === 'pdf' && ext !== 'pdf') {
      return '“' + file.name + '” is not a PDF. Only PDF files (.pdf) are accepted here.';
    }
    if (kind === 'image' && imageExt.indexOf(ext) === -1) {
      return '“' + file.name + '” is not a supported image. Supported image formats: JPG, PNG, GIF and WebP.';
    }
    if (file.size === 0) return '“' + file.name + '” is empty. Please choose a different file.';
    if (file.size > max) {
      return '“' + file.name + '” is ' + mb(file.size) + ', which is over the ' + mb(max) + ' limit. Please choose a smaller file.';
    }
    return null;
  }

  input.addEventListener('change', function () {
    clearError();
    if (input.files.length) {
      var msg = check(input.files[0]);
      if (msg) showError(msg);
    }
  });

  form.addEventListener('submit', function (ev) {
    clearError();
    if (!input.files.length) {
      ev.preventDefault();
      showError('Please choose a ' + (kind === 'pdf' ? 'PDF' : 'image') + ' file to upload.');
      return;
    }
    var msg = check(input.files[0]);
    if (msg) { ev.preventDefault(); showError(msg); return; }
    btn.disabled = true;
    btn.textContent = 'Uploading…';
  });

  // ---- Confirm before deleting ----
  document.querySelectorAll('form.js-delete').forEach(function (f) {
    f.addEventListener('submit', function (ev) {
      if (!confirm('Delete “' + f.dataset.name + '”? This cannot be undone.')) ev.preventDefault();
    });
  });

  // ---- Image viewer ----
  var box = document.getElementById('lightbox');
  if (box) {
    var big = box.querySelector('img'), cap = box.querySelector('p');
    document.querySelectorAll('img.thumb').forEach(function (img) {
      img.addEventListener('click', function () {
        big.src = img.dataset.full; big.alt = img.dataset.caption; cap.textContent = img.dataset.caption;
        box.classList.add('open');
      });
    });
    var close = function () { box.classList.remove('open'); big.removeAttribute('src'); };
    box.addEventListener('click', close);
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') close(); });
  }
})();
</script>
<?php require 'includes/footer.php'; ?>
