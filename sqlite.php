<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>SQLite Migration Generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body class="bg-gradient-to-br from-slate-100 to-slate-200 min-h-screen flex items-center justify-center">

<div class="w-full max-w-5xl px-6">
    <div class="bg-white rounded-2xl shadow-xl p-8">

        <!-- Header -->
        <div class="flex items-center gap-4 mb-8">
            <div class="bg-emerald-100 text-emerald-600 p-3 rounded-xl">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 8l4-4m0 0l-4-4m4 4H3
                             M7 16l-4 4m0 0l4 4m-4-4h18"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">
                    SQLite → Laravel Migration
                </h1>
                <p class="text-sm text-slate-500">
                    Generate migration langsung dari file SQLite
                </p>
            </div>
        </div>

        <!-- Steps -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 text-sm">
            <div class="bg-slate-50 border rounded-lg p-4">
                <b>1.</b> Upload file <code>.sqlite</code> / <code>.db</code>
            </div>
            <div class="bg-slate-50 border rounded-lg p-4">
                <b>2.</b> Pilih tabel
            </div>
            <div class="bg-slate-50 border rounded-lg p-4">
                <b>3.</b> Copy migration
            </div>
        </div>

        <!-- Upload -->
        <label class="block mb-4">
            <div class="flex items-center justify-center border-2 border-dashed rounded-xl p-6
                        hover:border-emerald-400 transition cursor-pointer bg-slate-50">
                <div class="text-center">
                    <p class="font-medium text-slate-700">Upload SQLite Database</p>
                    <p class="text-xs text-slate-500 mt-1">.sqlite atau .db</p>
                </div>
            </div>
            <input type="file" id="dbFile" class="hidden" accept=".sqlite,.db">
        </label>

        <!-- Table -->
        <select id="tableSelect"
            class="w-full border rounded-lg p-3 mb-6 disabled:bg-slate-100"
            disabled>
            <option value="">Pilih tabel</option>
        </select>

        <!-- Result -->
        <div class="relative">
            <pre id="result"
                class="bg-slate-900 text-green-400 rounded-xl p-5 text-sm
                       min-h-[240px] overflow-x-auto">
-- Migration akan muncul di sini
            </pre>

            <div id="loading"
                class="absolute inset-0 bg-slate-900/80 text-white
                       flex items-center justify-center hidden rounded-xl">
                Generating migration...
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 mt-6">
            <button id="copyBtn"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700
                       text-white px-5 py-2.5 rounded-lg transition disabled:opacity-50"
                disabled>
                Copy Migration
            </button>

            <a href="index.php"
                class="inline-flex items-center gap-2 border px-5 py-2.5 rounded-lg">
                Back to MySQL
            </a>
        </div>

    </div>
</div>

<script>
$('#dbFile').on('change', function () {
    let form = new FormData();
    form.append('db', this.files[0]);

    $('#tableSelect').prop('disabled', true).html('<option>Loading...</option>');

    $.ajax({
        url: 'ajax_sqlite_tables.php',
        method: 'POST',
        data: form,
        contentType: false,
        processData: false,
        success(res) {
            let tables = JSON.parse(res);
            let html = '<option value="">Pilih tabel</option>';
            tables.forEach(t => html += `<option>${t}</option>`);
            $('#tableSelect').html(html).prop('disabled', false);
        }
    });
});

$('#tableSelect').on('change', function () {
    if (!this.value) return;

    let form = new FormData();
    form.append('db', $('#dbFile')[0].files[0]);
    form.append('table', this.value);

    $('#loading').removeClass('hidden');

    $.ajax({
        url: 'ajax_sqlite_generate.php',
        method: 'POST',
        data: form,
        contentType: false,
        processData: false,
        success(res) {
            $('#result').text(JSON.parse(res).script);
            $('#copyBtn').prop('disabled', false);
            $('#loading').addClass('hidden');
        }
    });
});

$('#copyBtn').click(function () {
    navigator.clipboard.writeText($('#result').text());
    this.innerText = 'Copied!';
    setTimeout(() => this.innerText = 'Copy Migration', 1200);
});
</script>

</body>
</html>
