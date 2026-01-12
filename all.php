<?php
require 'config.php';

if (!isset($_GET['db'])) {
    exit('Database tidak dipilih.');
}

$database = $_GET['db'];
$pdo->exec("USE `$database`");

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


$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

$output = [];

foreach ($tables as $table) {
    $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_OBJ);

    $script = "Schema::create('$table', function (Blueprint \$table) {\n";
    $foreigns = [];

    foreach ($columns as $col) {
        if ($col->Field == 'id') {
            $script .= "    \$table->id();\n";
            continue;
        }

        if (in_array($col->Field, ['created_at', 'updated_at'])) continue;

        if (str_ends_with($col->Field, '_id')) {
            $related = str_replace('_id', '', $col->Field);
            $foreigns[] =
                "    \$table->foreignId('{$col->Field}')\n" .
                "          ->nullable()\n" .
                "          ->constrained('{$related}s')\n" .
                "          ->nullOnDelete();\n";
            continue;
        }

        $script .= convertColumn($col);
    }

    $script .= "    \$table->timestamps();\n";

    foreach ($foreigns as $f) $script .= $f;

    $script .= "});";

    $output[$table] = $script;
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>All Migration Scripts</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 min-h-screen">

    <div class="max-w-6xl mx-auto px-6 py-8">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">All Migration Generator</h1>
                <p class="text-sm text-slate-500">Database: <b><?= $database ?></b></p>
            </div>

            <a href="index.php" class="px-4 py-2 rounded-lg border">Kembali</a>
        </div>

        <?php foreach ($output as $table => $script): ?>
            <div class="bg-white rounded-xl shadow mb-6 overflow-hidden">

                <div class="flex items-center justify-between px-5 py-3 border-b">
                    <div class="font-medium"><?= $table ?></div>
                    <button class="copy-btn border px-3 py-1.5 rounded-md">Copy</button>
                </div>

                <pre class="migration-code bg-slate-900 text-green-400 p-5 text-sm overflow-x-auto"><?= htmlspecialchars($script) ?></pre>

            </div>
        <?php endforeach ?>

    </div>

    <script>
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const code = this.closest('.rounded-xl').querySelector('.migration-code').innerText;
                navigator.clipboard.writeText(code);
                this.innerText = 'Copied';
                setTimeout(() => this.innerText = 'Copy', 1200);
            });
        });
    </script>

</body>

</html>