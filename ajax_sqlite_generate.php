<?php

if (!isset($_FILES["db"], $_POST["table"])) {
    exit();
}

$tmp = $_FILES["db"]["tmp_name"];
$table = $_POST["table"];

$pdo = new PDO("sqlite:$tmp");

$cols = $pdo->query("PRAGMA table_info('$table')")->fetchAll(PDO::FETCH_OBJ);

$script = "Schema::create('$table', function (Blueprint \$table) {\n";

foreach ($cols as $c) {
    if ($c->pk) {
        $script .= "    \$table->id();\n";
        continue;
    }

    $type = strtolower($c->type);

    if (str_contains($type, "int")) {
        $line = "\$table->integer";
    } elseif (str_contains($type, "char") || str_contains($type, "text")) {
        $line = "\$table->string";
    } elseif (str_contains($type, "real") || str_contains($type, "float")) {
        $line = "\$table->float";
    } elseif (str_contains($type, "date") || str_contains($type, "time")) {
        $line = "\$table->dateTime";
    } else {
        $line = "\$table->string";
    }

    $line = "    $line('{$c->name}')";

    if (!$c->notnull) {
        $line .= "->nullable()";
    }
    if ($c->dflt_value !== null) {
        $line .= "->default({$c->dflt_value})";
    }

    $script .= $line . ";\n";
}

$script .= "});";

echo json_encode(["script" => $script]);
