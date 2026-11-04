<?php
// Helpers for the Library (PDF books + images).
// Requires config.php to be loaded first ($mysqli, session, e()).

define('MAX_UPLOAD_BYTES', 25 * 1024 * 1024);          // 25 MB per file
define('UPLOAD_DIR', dirname(__DIR__) . '/storage');   // blocked from the web by storage/.htaccess

// Image formats we accept -> extension we store them with.
// SVG is deliberately NOT allowed (it can contain scripts).
const IMAGE_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// ---------- CSRF ----------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_ok(): bool {
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '');
}

// ---------- Small formatting helpers ----------
function format_bytes(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024) . ' KB';
    return $bytes . ' B';
}

function clean_filename(string $name): string {
    $name = basename(str_replace('\\', '/', $name));
    $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
    $name = trim($name);
    if ($name === '') $name = 'untitled';
    return mb_substr($name, 0, 200);
}

// ---------- Validation ----------
// Returns ['error' => string|null, 'ext' => string, 'mime' => string]
function validate_upload(string $kind, array $f): array {
    $fail = fn(string $msg) => ['error' => $msg, 'ext' => '', 'mime' => ''];

    $label    = $kind === 'pdf' ? 'PDF' : 'image';
    $name     = clean_filename($f['name'] ?? '');
    $limitTxt = format_bytes(MAX_UPLOAD_BYTES);
    $formats  = $kind === 'pdf'
        ? 'Only PDF files (.pdf) are accepted here.'
        : 'Supported image formats: JPG, PNG, GIF and WebP.';

    switch ($f['error'] ?? UPLOAD_ERR_NO_FILE) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return $fail("Please choose a $label file to upload.");
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return $fail("“$name” is too large. The maximum size is $limitTxt per file.");
        case UPLOAD_ERR_PARTIAL:
            return $fail("The upload of “$name” was interrupted. Please try again.");
        default:
            return $fail("Sorry, “$name” could not be uploaded because of a server problem. Please try again.");
    }

    $size = (int) ($f['size'] ?? 0);
    if ($size > MAX_UPLOAD_BYTES) {
        return $fail("“$name” is " . format_bytes($size) . ", which is over the $limitTxt limit. Please choose a smaller file.");
    }
    if ($size === 0) {
        return $fail("“$name” is empty. Please choose a different file.");
    }
    if (!is_uploaded_file($f['tmp_name'])) {
        return $fail('The upload could not be verified. Please try again.');
    }

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if ($kind === 'pdf') {
        if ($ext !== 'pdf') {
            return $fail("“$name” is not a PDF. $formats");
        }
        // A real PDF starts with "%PDF-" (allowed anywhere in the first 1024 bytes).
        $head = (string) file_get_contents($f['tmp_name'], false, null, 0, 1024);
        if (strpos($head, '%PDF-') === false) {
            return $fail("“$name” does not look like a valid PDF file. $formats");
        }
        return ['error' => null, 'ext' => 'pdf', 'mime' => 'application/pdf'];
    }

    // image
    if (!in_array($ext, IMAGE_EXTENSIONS, true)) {
        return $fail("“$name” is not a supported image. $formats");
    }
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !isset(IMAGE_TYPES[$info['mime']])) {
        return $fail("“$name” is not a valid image, or its format is not supported. $formats");
    }
    return ['error' => null, 'ext' => IMAGE_TYPES[$info['mime']], 'mime' => $info['mime']];
}

// ---------- Storing ----------
// Validates, moves the file into storage/ and records it. Returns an error string or null on success.
function save_upload(mysqli $db, int $userId, string $kind, array $f): ?string {
    $v = validate_upload($kind, $f);
    if ($v['error']) return $v['error'];

    if (!is_dir(UPLOAD_DIR) && !@mkdir(UPLOAD_DIR, 0755, true)) {
        return 'The server could not create the storage folder. Please contact the site owner.';
    }
    if (!is_writable(UPLOAD_DIR)) {
        return 'The storage folder is not writable. Please contact the site owner.';
    }

    $stored = bin2hex(random_bytes(16)) . '.' . $v['ext'];   // random name; user input never touches the path
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . '/' . $stored)) {
        return 'The file could not be saved. Please try again.';
    }

    $orig = clean_filename($f['name']);
    $size = (int) filesize(UPLOAD_DIR . '/' . $stored);
    $stmt = $db->prepare(
        'INSERT INTO user_files (user_id, kind, original_name, stored_name, mime, size_bytes, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->bind_param('issssi', $userId, $kind, $orig, $stored, $v['mime'], $size);
    if (!$stmt->execute()) {
        @unlink(UPLOAD_DIR . '/' . $stored);   // don't leave an orphan file behind
        return 'The file could not be recorded in your library. Please try again.';
    }
    return null;
}

// ---------- Lookup / delete (always scoped to the owner) ----------
function find_user_file(mysqli $db, int $id, int $userId, ?string $kind = null): ?array {
    $sql = 'SELECT * FROM user_files WHERE id = ? AND user_id = ?' . ($kind ? ' AND kind = ?' : '');
    $stmt = $db->prepare($sql);
    if ($kind) $stmt->bind_param('iis', $id, $userId, $kind);
    else       $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function delete_user_file(mysqli $db, int $id, int $userId): bool {
    $row = find_user_file($db, $id, $userId);
    if (!$row) return false;

    $stmt = $db->prepare('DELETE FROM user_files WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();

    $path = UPLOAD_DIR . '/' . $row['stored_name'];
    if (is_file($path)) @unlink($path);
    return true;
}
