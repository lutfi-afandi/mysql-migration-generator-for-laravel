<?php

if (!isset($_FILES["db"])) {
    exit();
}

$tmp = $_FILES["db"]["tmp_name"];
$pdo = new PDO("sqlite:$tmp");

$tables = $pdo
    ->query(
        "
    SELECT name FROM sqlite_master
    WHERE type='table' AND name NOT LIKE 'sqlite_%'
",
    )
    ->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($tables);
