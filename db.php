<?php
require_once 'config.php';

function loadDB() {
    return json_decode(file_get_contents(DB_FILE), true);
}

function saveDB($data) {
    file_put_contents(DB_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

function fmt($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}
?>