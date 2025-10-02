<?php
require_once __DIR__ . '/config.php';

// 2) Then load the PDO wrapper
require_once __DIR__ . '/db.php';

session_destroy();
header("Location: /");
die();