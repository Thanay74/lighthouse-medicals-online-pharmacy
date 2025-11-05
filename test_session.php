<?php
session_start();

if (!isset($_SESSION['test'])) {
    $_SESSION['test'] = 'Hello, World!';
}

echo 'Session ID: ' . session_id() . '<br>';
echo 'Session Data: ' . print_r($_SESSION, true);
?> 