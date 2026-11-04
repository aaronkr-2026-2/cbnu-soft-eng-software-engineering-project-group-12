<?php
// Streams one of the logged-in user's own files. Nobody else's file can be reached,
// because the lookup is always filtered by the current user's id.
require 'config.php';
require_login();
require 'includes/files.php';

$id  = (int) ($_GET['id'] ?? 0);
$row = find_user_file($mysqli, $id, current_user_id());
$path = $row ? UPLOAD_DIR . '/' . $row['stored_name'] : '';

if (!$row || !is_file($path)) {
    http_response_code(404);
    exit('File not found.');
}

session_write_close();   // don't block other requests while a big file streams

$download = isset($_GET['download']);
$ascii    = preg_replace('/[^A-Za-z0-9._-]/', '_', $row['original_name']);
$disp     = $download ? 'attachment' : 'inline';

header('Content-Type: ' . $row['mime']);
header('Content-Length: ' . filesize($path));
header("Content-Disposition: $disp; filename=\"$ascii\"; filename*=UTF-8''" . rawurlencode($row['original_name']));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');

while (ob_get_level() > 0) ob_end_clean();
readfile($path);
exit;
