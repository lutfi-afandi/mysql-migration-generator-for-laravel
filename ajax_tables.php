<?php
require 'config.php';

if (!isset($_POST['database'])) {
    http_response_code(400);
    exit;
}

$db = $_POST['database'];

try {
    $pdo->exec("USE `$db`");

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($tables);
} catch (Exception $e) {
    echo json_encode([]);
}
