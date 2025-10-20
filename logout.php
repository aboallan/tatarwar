<?php
require_once __DIR__ . '/includes/functions.php';
ensure_session();

unset($_SESSION['user']);
session_regenerate_id(true);

header('Location: login.php');
exit;
