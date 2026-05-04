<?php
try {
    $db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
    echo "OK\n";
} catch (Exception $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}
