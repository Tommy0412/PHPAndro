<?php
header("Content-Type: text/html; charset=utf-8");

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        case 'phpinfo':
            ob_start();
            phpinfo();
            echo ob_get_clean();
            break;

        case 'extensions':
            echo json_encode(get_loaded_extensions(), JSON_PRETTY_PRINT);
            break;

        case 'phpversion':
            echo json_encode(['version' => phpversion(), 'sapi' => php_sapi_name()]);
            break;

        case 'ini':
            echo json_encode(ini_get_all(null, false), JSON_PRETTY_PRINT);
            break;

        case 'env':
            echo json_encode(['server' => $_SERVER, 'env' => getenv()], JSON_PRETTY_PRINT);
            break;

        case 'status':
            $status = [
                'php_uname' => php_uname(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'disk_free' => disk_free_space(__DIR__),
                'loaded_ini' => php_ini_loaded_file(),
                'loaded_extensions_count' => count(get_loaded_extensions()),
            ];
            echo json_encode($status, JSON_PRETTY_PRINT);
            break;

        case 'curltest':
            $url = "https://www.google.com";
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $data = curl_exec($ch);
            $err = curl_error($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            echo json_encode([
                'url' => $url,
                'success' => $data !== false,
                'http_code' => $info['http_code'] ?? null,
                'response_length' => strlen($data ?? ''),
                'error' => $err ?: null
            ], JSON_PRETTY_PRINT);
            break;

        case 'openssl':
            $testStr = "Hello PHP-Android";
            $cipher = "aes-128-cbc";
            $key = "testkey123456789";
            $iv = substr(hash('sha256', 'ivstring'), 0, 16);
            $encrypted = openssl_encrypt($testStr, $cipher, $key, 0, $iv);
            $decrypted = openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
            echo json_encode([
                'openssl_version' => OPENSSL_VERSION_TEXT,
                'cipher' => $cipher,
                'original' => $testStr,
                'encrypted' => $encrypted,
                'decrypted' => $decrypted,
                'match' => $testStr === $decrypted
            ], JSON_PRETTY_PRINT);
            break;

        case 'filesystem':
            $tmpFile = ini_get('upload_tmp_dir') . '/phpandroid_test.txt';
            $data = "PHPAndro test file\n" . date('c');
            $write = file_put_contents($tmpFile, $data);
            $read = @file_get_contents($tmpFile);
            echo json_encode([
                'temp_dir' => sys_get_temp_dir(),
                'write_success' => $write !== false,
                'read_success' => $read !== false,
                'read_content' => $read,
                'exists_after' => file_exists($tmpFile)
            ], JSON_PRETTY_PRINT);
            break;

        case 'sqlite':
            try {
                $tmpDir = ini_get('upload_tmp_dir');
                if (!$tmpDir) $tmpDir = __DIR__;
                $dbPath = $tmpDir . '/phpandroid_test.db';
                $db = new SQLite3($dbPath);
                $db->exec("CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY, txt TEXT)");
                $db->exec("INSERT INTO test (txt) VALUES ('Hello SQLite from Android')");
                $res = $db->query("SELECT * FROM test");
                $rows = [];
                while ($r = $res->fetchArray(SQLITE3_ASSOC)) $rows[] = $r;
                $db->close();
                echo json_encode([
                    'db_path' => $dbPath,
                    'rows' => $rows,
                    'success' => true
                ], JSON_PRETTY_PRINT);
            } catch (Throwable $e) {
                echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
            }
            break;

        case 'network':
            $hostname = gethostname();
            $ipLocal = gethostbyname($hostname);
            $ipExternal = @file_get_contents('https://api.ipify.org');
            echo json_encode([
                'hostname' => $hostname,
                'local_ip' => $ipLocal,
                'external_ip' => $ipExternal ?: 'unavailable',
                'dns_google' => gethostbyname('google.com')
            ], JSON_PRETTY_PRINT);
            break;

        case 'timezone':
            echo json_encode([
                'default_timezone' => date_default_timezone_get(),
                'current_time' => date('c'),
                'locale' => setlocale(LC_ALL, 0)
            ], JSON_PRETTY_PRINT);
            break;

        // Test HTTP Methods
        case 'http_methods':
            $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
            $results = [];

            foreach ($methods as $method) {
                $endpoint = strtolower($method);
                if ($endpoint === 'patch') {
                    $endpoint = 'patch';
                }
                $url = "https://postman-echo.com/$endpoint";

                $ch = curl_init($url);

                $postFields = in_array($method, ['POST', 'PUT', 'PATCH']) ? json_encode(['test' => 'data']) : null;

                curl_setopt_array($ch, [
                    CURLOPT_CUSTOMREQUEST => $method,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_POSTFIELDS => $postFields
                ]);

                $response = curl_exec($ch);
                $error = curl_error($ch);
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $results[$method] = [
                    'url' => $url,
                    'status' => $status,
                    'error' => $error,
                    'response' => $response
                ];
            }

            header('Content-Type: application/json');
            echo json_encode($results, JSON_PRETTY_PRINT);
            break;


        // DOMDocument test
        case 'dom':
            $html = "<html><body><h1>Hello DOMDocument</h1><p>Current time: " . date('c') . "</p></body></html>";
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $nodes = [];
            foreach ($dom->getElementsByTagName('*') as $el) {
                $nodes[] = [
                    'tag' => $el->tagName,
                    'content' => trim($el->textContent)
                ];
            }
            echo json_encode($nodes, JSON_PRETTY_PRINT);
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
            break;
    }
    exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>PHPAndro Diagnostic</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        pre {
            white-space: pre-wrap;
            word-break: break-word;
        }

        .card {
            @apply bg-white/80 dark:bg-gray-800/70 backdrop-blur rounded-2xl shadow-lg p-4;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-sky-400 via-indigo-500 to-violet-600 min-h-screen text-gray-800 dark:text-gray-100">
    <div class="max-w-5xl mx-auto p-6">
        <header class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-extrabold">PHPAndro Diagnostic</h1>
            <div class="text-xs opacity-80">
                PHP <span id="phpVer">...</span> • SAPI <span id="phpSapi">...</span>
            </div>
        </header>

        <main class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2 card">
                <h2 class="font-semibold mb-3">Test Actions</h2>
                <div class="grid grid-cols-2 gap-3">
                    <button class="action-btn p-2 bg-blue-600 text-white rounded" data-action="phpinfo">phpinfo()</button>
                    <button class="action-btn p-2 bg-blue-600 text-white rounded" data-action="extensions">Extensions</button>
                    <button class="action-btn p-2 bg-blue-600 text-white rounded" data-action="phpversion">Version</button>
                    <button class="action-btn p-2 bg-blue-600 text-white rounded" data-action="ini">INI</button>
                    <button class="action-btn p-2 bg-blue-600 text-white rounded" data-action="env">Env</button>
                    <button class="action-btn p-2 bg-blue-600 text-white rounded" data-action="status">Status</button>
                    <button class="action-btn p-2 bg-emerald-600 text-white rounded" data-action="curltest">cURL Test</button>
                    <button class="action-btn p-2 bg-emerald-600 text-white rounded" data-action="openssl">OpenSSL Test</button>
                    <button class="action-btn p-2 bg-emerald-600 text-white rounded" data-action="filesystem">Filesystem</button>
                    <button class="action-btn p-2 bg-emerald-600 text-white rounded" data-action="sqlite">SQLite Test</button>
                    <button class="action-btn p-2 bg-emerald-600 text-white rounded" data-action="network">Network</button>
                    <button class="action-btn p-2 bg-emerald-600 text-white rounded" data-action="timezone">Timezone</button>
                    <button class="action-btn p-2 bg-purple-600 text-white rounded" data-action="http_methods">HTTP Methods Test</button>
                    <button class="action-btn p-2 bg-purple-600 text-white rounded" data-action="dom">DOMDocument Test</button>
                </div>

                <div id="output" class="mt-4 bg-gray-50 dark:bg-gray-900 p-3 rounded-lg overflow-auto" style="max-height:60vh">
                    <div class="text-sm opacity-70">Press a button to run an action.</div>
                </div>
            </div>

            <aside class="card">
                <h3 class="font-semibold mb-2">Tools</h3>
                <div class="space-y-2 text-sm">
                    <button id="copyBtn" class="w-full p-2 rounded bg-indigo-600 text-white">Copy</button>
                    <button id="downloadBtn" class="w-full p-2 rounded bg-emerald-600 text-white">Download</button>
                    <button id="clearBtn" class="w-full p-2 rounded bg-red-600 text-white">Clear</button>
                </div>
            </aside>
        </main>

        <footer class="mt-6 text-xs opacity-80 text-center">Last updated <?= date('Y-m-d H:i') ?></footer>
    </div>

    <script>
        const output = document.getElementById('output');
        let lastOutput = '';

        async function callAction(action) {
            output.innerHTML = 'Loading...';
            const res = await fetch('?action=' + action);

            if (action === 'phpinfo') {
                output.innerHTML = await res.text();
                return;
            }

            const json = await res.text();
            try {
                const data = JSON.parse(json);

                if (action === 'http_methods') {
                    let html = '';
                    for (let method in data) {
                        html += `<details class="mb-2"><summary class="font-semibold">${method}</summary><pre>${escapeHtml(data[method])}</pre></details>`;
                    }
                    output.innerHTML = html;
                    lastOutput = JSON.stringify(data, null, 2);
                    return;
                }

                lastOutput = JSON.stringify(data, null, 2);
                output.innerHTML = '<pre>' + escapeHtml(lastOutput) + '</pre>';
            } catch {
                output.innerHTML = '<pre>' + escapeHtml(json) + '</pre>';
            }
        }

        document.querySelectorAll('.action-btn').forEach(b => b.onclick = () => callAction(b.dataset.action));

        function escapeHtml(s) {
            return s.replace(/[&<>]/g, m => ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;"
            } [m]));
        }

        document.getElementById('copyBtn').onclick = () => navigator.clipboard.writeText(lastOutput || output.innerText);
        document.getElementById('downloadBtn').onclick = () => {
            const blob = new Blob([lastOutput || output.innerText], {
                type: 'text/plain'
            });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'php-output.txt';
            a.click();
        }
        document.getElementById('clearBtn').onclick = () => {
            output.innerHTML = 'Cleared.';
        }

        fetch('?action=phpversion').then(r => r.json()).then(i => {
            document.getElementById('phpVer').innerText = i.version;
            document.getElementById('phpSapi').innerText = i.sapi;
        });
    </script>
</body>

</html>