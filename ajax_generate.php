<?php
require 'config.php';

if (!isset($_POST['database'], $_POST['table'])) {
    http_response_code(400);
    exit;
}

$db = $_POST['database'];
$table = $_POST['table'];

$pdo->exec("USE `$db`");

$columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_OBJ);

function convertColumn($col)
{
    if ($col->Key == 'PRI') return "    \$table->id();\n";

    $type = strtolower($col->Type);
    $line = "    ";

    if (str_contains($type, 'varchar')) {
        preg_match('/\d+/', $type, $m);
        $length = $m[0] ?? 255;
        $line .= "\$table->string('{$col->Field}', $length)";
    } elseif (str_contains($type, 'char')) {
        preg_match('/\d+/', $type, $m);
        $length = $m[0] ?? 1;
        $line .= "\$table->char('{$col->Field}', $length)";
    } elseif (str_contains($type, 'bigint')) {
        $line .= "\$table->bigInteger('{$col->Field}')";
    } elseif (str_contains($type, 'int')) {
        $line .= "\$table->integer('{$col->Field}')";
    } elseif (str_contains($type, 'decimal')) {
        preg_match_all('/\d+/', $type, $m);
        $line .= "\$table->decimal('{$col->Field}', {$m[0][0]}, {$m[0][1]})";
    } elseif (str_contains($type, 'float')) {
        $line .= "\$table->float('{$col->Field}')";
    } elseif (str_contains($type, 'double')) {
        $line .= "\$table->double('{$col->Field}')";
    } elseif (str_contains($type, 'boolean') || $type == 'tinyint(1)') {
        $line .= "\$table->boolean('{$col->Field}')";
    } elseif (str_contains($type, 'json')) {
        $line .= "\$table->json('{$col->Field}')";
    } elseif (str_contains($type, 'enum')) {
        preg_match("/\((.*)\)/", $type, $m);
        $values = $m[1] ?? '';
        $line .= "\$table->enum('{$col->Field}', [$values])";
    } elseif (str_contains($type, 'date') && !str_contains($type, 'datetime')) {
        $line .= "\$table->date('{$col->Field}')";
    } elseif (str_contains($type, 'datetime')) {
        $line .= "\$table->dateTime('{$col->Field}')";
    } elseif (str_contains($type, 'timestamp')) {
        $line .= "\$table->timestamp('{$col->Field}')";
    } elseif (str_contains($type, 'time')) {
        $line .= "\$table->time('{$col->Field}')";
    } elseif (str_contains($type, 'year')) {
        $line .= "\$table->year('{$col->Field}')";
    } else {
        $line .= "\$table->string('{$col->Field}')";
    }

    if (str_contains($type, 'unsigned')) {
        $line .= "->unsigned()";
    }

    if ($col->Null == 'YES') {
        $line .= "->nullable()";
    }

    if ($col->Default !== null) {
        if ($col->Default === 'CURRENT_TIMESTAMP') {
            $line .= "->useCurrent()";
        } else {
            $line .= "->default('{$col->Default}')";
        }
    }

    return $line . ";\n";
}


$script = "Schema::create('$table', function (Blueprint \$table) {\n";

foreach ($columns as $col) {
    $script .= convertColumn($col);
}

$script .= "});";

echo json_encode(['script' => $script]);
