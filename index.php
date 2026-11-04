<?php
require 'config.php';
header('Location: ' . (current_user_id() ? 'words.php' : 'login.php'));
exit;
